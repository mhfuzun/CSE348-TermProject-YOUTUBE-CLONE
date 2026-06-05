<?php

include_once __DIR__ . "/../config/database_cfg.php";

include_once __DIR__ . "/../Security/Common.php";
include_once __DIR__ . "/../Security/Password.php";

include_once __DIR__ . "/../Database/DatabaseAdapterInterface.php";
include_once __DIR__ . "/../Database/PdoDatabaseAdapter.php";

include_once __DIR__ . "/../Services/Service.php";
include_once __DIR__ . "/../Services/AuthService.php";
include_once __DIR__ . "/../Services/WatchService.php";
include_once __DIR__ . "/../Services/ChannelService.php";
include_once __DIR__ . "/../Services/FeedService.php";
include_once __DIR__ . "/../Services/CommentService.php";

include_once __DIR__ . "/../Controller/Controller.php";

include_once __DIR__ . "/../Session/SessionController.php";
include_once __DIR__ . "/../Core/View.php";
include_once __DIR__ . "/../Core/Router.php";

include_once __DIR__ . "/../Model/User.php";
include_once __DIR__ . "/../Model/UserAccount.php";
include_once __DIR__ . "/../Model/Video.php";
include_once __DIR__ . "/../Model/VideoContent.php";
include_once __DIR__ . "/../Model/Channel.php";
include_once __DIR__ . "/../Model/ChannelPage.php";
include_once __DIR__ . "/../Model/FeedPage.php";
include_once __DIR__ . "/../Model/Comment.php";
include_once __DIR__ . "/../Model/CommentContent.php";
include_once __DIR__ . "/../Model/WatchWindow.php";

include_once __DIR__ . "/../Repository/Repository.php";
include_once __DIR__ . "/../Repository/UserRepository.php";
include_once __DIR__ . "/../Repository/VideoRepository.php";
include_once __DIR__ . "/../Repository/ChannelRepository.php";
include_once __DIR__ . "/../Repository/SubscribeRepository.php";
include_once __DIR__ . "/../Repository/CommentRepository.php";

include_once __DIR__ . "/../pages/content/videoCard.php";
include_once __DIR__ . "/../pages/content/videoBlock.php";
include_once __DIR__ . "/../pages/content/commentCard.php";
include_once __DIR__ . "/../pages/content/addCommentBar.php";

class App {
    private Router $router;
    private PdoDatabaseAdapter $db;
    private SessionController $sessionController;

    public function __construct() {
        // router oluştur
        $this->router = new Router();

        // get ve set metodlarını ekle
        $this->router->get('login.php', 'ControlLogin@getLogin');
        $this->router->post('login.php', 'ControlLogin@postLogin');
        $this->router->get('logout.php', 'ControlLogin@getLogout');
        $this->router->get('register.php', 'ControlRegister@getRegister');
        $this->router->post('register.php', 'ControlRegister@postRegister');
        $this->router->get('watch.php', 'ControlWatch@getWatch');
        $this->router->get('channel.php', 'ControlChannel@getChannel');
        $this->router->get('feed.php', 'ControlFeed@getFeed');
        $this->router->get('api.php/subscribe', 'ControlChannel@subscribe');
        $this->router->get('api.php/unsubscribe', 'ControlChannel@unSubscribe');
        $this->router->get('api.php/deletecomment', 'ControlComment@deleteComment');
        $this->router->get('api.php/likevideo', 'ControlWatch@likeVideo');
        $this->router->post('api.php/createcomment', 'ControlComment@createComment');

        // controlüc işlerini oluştur
        $this->db = new PdoDatabaseAdapter(database_cfg::$DATABASE_CFG);

        // session kontrolcüsünü başlat
        $this->sessionController = new SessionController();
    }

    private function getController() : Controller {
        return new Controller(
            $this->db,
            $this->sessionController
        );
    }

    // uygulamayı yürüt.
    public function run() {
        $uri = $_SERVER['REQUEST_URI']; // browser'daki url
        $method = $_SERVER['REQUEST_METHOD']; // post mu? get mi?

        $view = $this->dispatch($uri, $method); // get/set array içinde ara

        if ($view !== null) {
            $view->addData("user", $this->sessionController->getUser());
            echo $view->render(); // embed the string value returned.
        }
    }

    public function dispatch($uri, $method) : ?View {
        return $this->router->dispatch(
            $uri,
            $method,
            $this->getController()
        );
    }

}
