<?php
require_once 'vendor/autoload.php';

//define token
define("GITLAB_TOKEN", "YOUR_SECRET_TOKEN");

//return object, see reference https://docs.gitlab.com/ee/user/project/integrations/webhooks.html
function getRequestBody(){
    return json_decode(file_get_contents('php://input'));
}

//send telegram, you can replace with email or anyting
function sendTelegramMessage($pesan){
    $TOKEN  = "YOUR_TELEGRAM_BOT_TOKEN";
    $chatid = "CHAT_ID";
    $method	= "sendMessage";
    $url    = "https://api.telegram.org/" . $TOKEN . "/". $method;
    $post = ['chat_id' => $chatid, 'text' => $pesan];
    $header = [
        "X-Requested-With: XMLHttpRequest",
        "User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/51.0.2704.84 Safari/537.36" 
    ];
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post );   
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $datas = curl_exec($ch);
    $error = curl_error($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $debug['text'] = $pesan;
    $debug['code'] = $status;
    $debug['status'] = $error;
    $debug['respon'] = json_decode($datas, true);
    print_r($debug);
}

//catch the hooks
Flight::route('GET|POST /', function(){
    if($_SERVER['HTTP_X_GITLAB_TOKEN']??"it's null" == GITLAB_TOKEN){
        $reqBody = getRequestBody();
        $message = "fail";
        switch ($_SERVER["HTTP_X_GITLAB_EVENT"]) {
            case 'Push Hook':
                $message = "[NOTIFICATION PUSH]\n"
                           . $reqBody->user_name 
                           . " push a commit on "
                           . $reqBody->repository->name . "\n"
                           . " [".$reqBody->repository->git_http_url."]\n\n"
                           . "ref : " . $reqBody->ref . "\n"
                           . "commit sha : " . $reqBody->commits[count($reqBody->commits) - 1]->id . "\n"
                           . "message : " . $reqBody->commits[count($reqBody->commits) - 1]->message ."\n"
                           . "url : " . $reqBody->commits[count($reqBody->commits) - 1]->url ."\n";
                break;
            case 'Tag Push Hook':
                $message = "[NOTIFICATION TAG PUSH]\n"
                            . $reqBody->user_name 
                            . " push a tag on "
                            . $reqBody->repository->name . "\n"
                            . " [".$reqBody->repository->git_http_url."]\n\n"
                            . "ref :" . $reqBody->ref . "\n"
                            . "commit sha : " . $reqBody->commits[count($reqBody->commits) - 1]->id . "\n"
                            . "message : " . $reqBody->commits[count($reqBody->commits) - 1]->message ."\n"
                            . "url : " . $reqBody->commits[count($reqBody->commits) - 1]->url ."\n";
                break;
            case 'Issue Hook':
                $message = "[NOTIFICATION ISSUE]\n"
                            . $reqBody->user->name 
                            . " raise an issue on "
                            . $reqBody->repository->name . "\n"
                            . " [".$reqBody->repository->url."]\n\n"
                            . "title : " . $reqBody->object_attributes->title . "\n"
                            . "description : " . $reqBody->object_attributes->description . "\n"
                            . "url : " . $reqBody->object_attributes->url;
                break;
            case 'Note Hook':
                $message = "[NOTIFICATION NOTE/COMMENT]\n"
                            . $reqBody->user->name 
                            . " commented/noted on "
                            . $reqBody->repository->name . "\n"
                            . " [".$reqBody->repository->url."]\n\n"
                            . "note : " . $reqBody->object_attributes->note . "\n"
                            . "url : " . $reqBody->object_attributes->url;
                break;
            case 'Merge Request Hook':
                $message = "[MERGE REQUEST]\n"
                            . $reqBody->user->name 
                            . " requesting merge on "
                            . $reqBody->repository->name . "\n"
                            . " [".$reqBody->repository->url."]\n\n"
                            . "source branch : " . $reqBody->object_attributes->source_branch . "\n"
                            . "target branch : " . $reqBody->object_attributes->target_branch . "\n"
                            . "merge status  : " . $reqBody->object_attributes->merge_status . "\n"
                            . "state : " . $reqBody->object_attributes->state . "\n"
                            . "last commit message : " . $reqBody->object_attributes->last_commit->message . "\n"
                            . "last commit url : " . $reqBody->object_attributes->last_commit->url . "\n"
                            . "MR url: " . $reqBody->object_attributes->url;
                break;
            case 'Wiki Page Hook':
                $message = "[WIKI UPDATED]\n"
                            . $reqBody->project->name . " [" . $reqBody->project->git_http_url . "]\n"
                            . "url : " . $reqBody->object_attributes->url;
                break;
            case 'Pipeline Hook':
                $message = "Pipeline trigerred";
                break;
            case 'Job Hook':
                $message = "Job trigerred";
                break;
            default:
                $message = "Not gitlab webhook's event triggered";
                break;
        }
        sendTelegramMessage($message);
    }else{
        echo "Welcome to webhooks.";
    }
});

Flight::start();
?>