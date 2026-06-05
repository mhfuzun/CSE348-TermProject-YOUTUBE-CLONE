<?php 
$sqlOutFileName = 'seed.sql';
$dataDir = 'txt_files';

$userCount = 500;
$channelCount = 76;
$videoCount = 300;
$subscriptionCount = 5000;
$commentCount = 500;

function write($message, $reset = false) {
    global $sqlOutFileName;

    if (!file_exists($sqlOutFileName)) {
        file_put_contents($sqlOutFileName, "");
    }

    if ($reset) {
        file_put_contents($sqlOutFileName, $message . PHP_EOL);
    } else {
        file_put_contents($sqlOutFileName, $message . PHP_EOL, FILE_APPEND);
    }
}

function readFileLinesToArray($filePath) {
    $lines = [];

    if (!file_exists($filePath)) {
        return $lines;
    }

    $file = fopen($filePath, "r");

    if (!$file) {
        return $lines;
    }

    while (($line = fgets($file)) !== false) {
        $lines[] = rtrim($line, "\r\n");
    }

    fclose($file);

    return $lines;
}


function randomDate() {
    $start = strtotime("-1 year");
    $end = time();

    $randomTimestamp = random_int($start, $end);

    return date("Y-m-d", $randomTimestamp);
}


function sqlValue(mysqli $conn, $value) {
    if ($value === null) {
        return "NULL";
    }

    $value = (string) $value;

    // Geçersiz UTF-8 karakterleri temizler/düzeltir
    $value = mb_convert_encoding($value, "UTF-8", "UTF-8");

    return "'" . $conn->real_escape_string($value) . "'";
}

function sqlRow(mysqli $conn, array $values) {
    $escapedValues = [];

    foreach ($values as $value) {
        $escapedValues[] = sqlValue($conn, $value);
    }

    return "(" . implode(", ", $escapedValues) . ")";
}

write("-- Oto generated SQL file.", true);

// create users
write("-- user table: ");

$sql = "INSERT INTO USERS (username, password, user_image, full_name, email, country, bio, joined_on) VALUES ";
write($sql);

$userNames = readFileLinesToArray($dataDir . '/usernames.txt');
$imageUrls = readFileLinesToArray($dataDir . '/image_links.txt');

for ($i = 0; $i < $userCount; $i++) {
    $name = $userNames[$i];
    $imageUrl = $imageUrls[rand(0, count($imageUrls)-1)];

    $password = $name;
    $fullName = "Syn. " . $name;
    $email = $name . "@gmail.com";
    $country = "Turkey";
    $bio = "Hello!, I'm " . $name;
    $joinedOn = randomDate();

    $sql = sqlRow($conn, [
        $name,
        $password,
        $imageUrl,
        $fullName,
        $email,
        $country,
        $bio,
        $joinedOn
    ]);
    write($sql . (($i == $userCount - 1) ? ';' : ','));
}

// create channels
write("-- channel table: ");

$sql = "INSERT INTO CHANNELS (owner_id, channel_image, name, description, category, created_on) VALUES ";
write($sql);

$cattegorys = readFileLinesToArray($dataDir . '/categories.txt');
$channelNames = readFileLinesToArray($dataDir . '/channel_names.txt');
$channelCount = min($channelCount, $userCount, count($channelNames));
$channelOwnerIds = range(1, $userCount);
shuffle($channelOwnerIds);

for ($i = 0; $i < $channelCount; $i++) {
    $ownerid = $channelOwnerIds[$i];
    $imageUrl = $imageUrls[rand(0, count($imageUrls)-1)];
    $name = $channelNames[$i];
    $description = "Channel of " . $name;
    $category = $cattegorys[rand(0, count($cattegorys)-1)];
    $createdOn = randomDate();

    $sql = sqlRow($conn, [
        $ownerid,
        $imageUrl,
        $name,
        $description,
        $category,
        $createdOn
    ]);
    write($sql . (($i == $channelCount - 1) ? ';' : ','));
}

// create video
write("-- video table: ");

$sql = "INSERT INTO VIDEOS (channel_id, title, description, url, duration_seconds, uploaded_at) VALUES ";
write($sql);

$videos = readFileLinesToArray($dataDir . '/videos.txt');
$videoRows = [];

foreach ($videos as $video) {
    $videoAtt = explode("\t", $video);

    if (count($videoAtt) < 5 || $videoAtt[0] === 'video_id') {
        continue;
    }

    $videoRows[] = $videoAtt;
}

$videoCount = min($videoCount, count($videoRows));

if ($videoCount < 1) {
    throw new RuntimeException("No valid video rows found in " . $dataDir . '/videos.txt');
}

for ($i = 0; $i < $videoCount; $i++) {
    $videoAtt = $videoRows[$i];
    
    $channelId = rand(1, $channelCount);
    $title = $videoAtt[2];
    $description = substr($videoAtt[3], 0, 1000);
    $videoUrl = $videoAtt[1];
    $duration = $videoAtt[4];
    $uploaded_at = randomDate();

    $sql = sqlRow($conn, [
        $channelId,
        $title,
        $description,
        $videoUrl,
        $duration,
        $uploaded_at
    ]);
    write($sql . (($i == $videoCount - 1) ? ';' : ','));
}

// create SUBSCRIPTIONS
write("-- subscription table: ");

$sql = "INSERT INTO SUBSCRIPTIONS (subscriber_id, channel_id, subscribed_at) VALUES ";
write($sql);

$maxSubscriptionPairs = $userCount * $channelCount;
$subscriptionCount = min($subscriptionCount, $maxSubscriptionPairs);
$subscriptionPairs = [];

for ($i = 0; $i < $subscriptionCount; $i++) {
    do {
        $subscriber_id = rand(1, $userCount);
        $channel_id = rand(1, $channelCount);
        $pairKey = $subscriber_id . '-' . $channel_id;
    } while (isset($subscriptionPairs[$pairKey]));

    $subscriptionPairs[$pairKey] = true;
    $subscribed_at = randomDate();

    $sql = sqlRow($conn, [
        $subscriber_id,
        $channel_id,
        $subscribed_at
    ]);
    write($sql . (($i == $subscriptionCount - 1) ? ';' : ','));
}

// create COMMENTS
write("-- comment table:");

$sql = "INSERT INTO COMMENTS (video_id, user_id, parent_comment_id, body, posted_at) VALUES ";
write($sql);

$comments = readFileLinesToArray($dataDir . '/comments.txt');
$replayQueue = [];
$parentVideoList = [];
$minReplyCount = 20;
$replyCount = 0;

for ($i = 0; $i < $commentCount; $i++) {
    $user_id = rand(1, $userCount);
    if (count($replayQueue) > 0) {
        $parent_comment_id = array_pop($replayQueue);
        $video_id = $parentVideoList[$parent_comment_id-1];
        $replyCount++;
    } else {
        $parent_comment_id = NULL;
        $video_id = rand(1, $videoCount);
    }
    $body = $comments[$i];
    $posted_at = randomDate();

    $queuedReplyCount = count($replayQueue);
    $newReplyCount = ($replyCount + $queuedReplyCount < $minReplyCount)
        ? 1
        : ((rand(0, 100) > 90) ? rand(0, 6) : 0);

    for ($x=0; $x<$newReplyCount; $x++) {
        $replayQueue[] = $i+1;
    }

    $parentVideoList[] = $video_id;
    $sql = sqlRow($conn, [
        $video_id,
        $user_id,
        $parent_comment_id,
        $body,
        $posted_at
    ]);
    write($sql . (($i == $commentCount - 1) ? ';' : ','));
}

?>
