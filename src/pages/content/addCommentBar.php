<?php function addCommentBar(int $videoId, ?User $user = null, ?int $parentCommentId = null) { ?>
    <?php
        $isReply = $parentCommentId !== null;
        $textareaId = ($isReply ? 'reply-body-' . $parentCommentId : 'comment-body-' . $videoId);
    ?>
    <?php if ($user === null) { ?>
        <div class="add-comment-login <?= $isReply ? 'add-comment-reply' : '' ?>">
            <i class="bi bi-chat-left-text"></i>
            <span>Please log in to <?= $isReply ? 'reply' : 'add a comment' ?>.</span>
            <a class="btn btn-sm btn-primary" href="<?= Common::createLinkToSitePage('login.php') ?>">
                Login
            </a>
        </div>
    <?php } else { ?>
        <form
            class="add-comment-bar <?= $isReply ? 'add-comment-reply' : '' ?>"
            action="<?= Common::createLinkToSitePage('api.php/createcomment') ?>"
            method="post"
        >
            <img
                class="add-comment-avatar"
                src="<?= View::e($user->getUserImage()) ?>"
                alt="<?= View::e($user->getUsername()) ?>"
            >

            <div class="add-comment-main">
                <label class="visually-hidden" for="<?= View::e($textareaId) ?>">
                    <?= $isReply ? 'Add a reply' : 'Add a comment' ?>
                </label>
                <textarea
                    id="<?= View::e($textareaId) ?>"
                    class="add-comment-input"
                    name="body"
                    rows="2"
                    maxlength="250"
                    placeholder="<?= $isReply ? 'Write a reply...' : 'Add a comment...' ?>"
                    required
                ></textarea>

                <div class="add-comment-actions">
                    <span class="add-comment-limit">Max 250 characters</span>
                    <?php if ($isReply) { ?>
                        <button class="btn btn-outline-secondary btn-sm add-comment-cancel" type="button">
                            Cancel
                        </button>
                    <?php } ?>
                    <button class="btn btn-primary btn-sm" type="submit">
                        <i class="bi bi-send"></i>
                        <?= $isReply ? 'Reply' : 'Comment' ?>
                    </button>
                </div>
            </div>

            <input type="hidden" name="video_id" value="<?= View::e($videoId) ?>">
            <input type="hidden" name="parent_comment_id" value="<?= View::e($parentCommentId ?? '') ?>">
        </form>
    <?php } ?>
<?php } ?>
