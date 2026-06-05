<?php

class ControlChannel {
    private Controller $controller;
    private ChannelService $channelService;

    public function __construct(Controller $controller) {
        $this->controller = $controller;
        $this->channelService = new ChannelService($this->controller->getDb());
    }

    public function getChannel() : ?View {
        $channelId = Common::get('channel_id');

        if (!is_string($channelId) || !ctype_digit($channelId)) {
            http_response_code(404);
            return new View('404', '404');
        }

        $channelPage = $this->channelService->createChannelPageFromChannelId($channelId);

        if ($channelPage !== null) {
            return new View(
                'channel',
                'Channel',
                [
                    'channelPage' => $channelPage
                ]
            );
        } else {
            http_response_code(404);
            return new View(
                '404',
                '404'
            );
        }
    }

    public function subscribe() : ?View {
        $channelId = Common::get('channel_id');

        if (!is_string($channelId) || !ctype_digit($channelId)) {
            http_response_code(404);
            return new View('404', '404');
        }

        $user = $this->controller->getSessionController()->getUser();

        if ($user === null) {
            http_response_code(401);
            return new View('401', '401');
        }

        $channel = $this->channelService->getChannelByChannelId($channelId);

        if ($channel === null) {
            http_response_code(404);
            return new View('404', '404');
        }

        if ($this->channelService->subscribeAChannelWithUser($user, $channel)) {
            header('Location: ' . Common::createLinkToSitePage('channel.php', ['channel_id' => $channelId]));
            return null;
        } else {
            http_response_code(500);
            return new View('500', '500');
        }
    }

    public function unSubscribe() : ?View {
        $channelId = Common::get('channel_id');

        if (!is_string($channelId) || !ctype_digit($channelId)) {
            http_response_code(404);
            return new View('404', '404');
        }

        $user = $this->controller->getSessionController()->getUser();

        if ($user === null) {
            http_response_code(401);
            return new View('401', '401');
        }

        $channel = $this->channelService->getChannelByChannelId($channelId);

        if ($channel === null) {
            http_response_code(404);
            return new View('404', '404');
        }

        if ($this->channelService->unsubscribeAChannelWithUser($user, $channel)) {
            header('Location: ' . Common::createLinkToSitePage('channel.php', ['channel_id' => $channelId]));
            return null;
        } else {
            http_response_code(500);
            return new View('500', '500');
        }
    }
}
