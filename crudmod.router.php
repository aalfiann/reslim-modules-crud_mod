<?php
//Define interface class for router
use \Psr\Http\Message\ServerRequestInterface as Request;        //PSR7 ServerRequestInterface   >> Each router file must contains this
use \Psr\Http\Message\ResponseInterface as Response;            //PSR7 ResponseInterface        >> Each router file must contains this

//Define your modules class
use \modules\crud_mod\CrudMod as CrudMod;                       //Your main modules class

//Define additional class for any purpose
use \classes\middleware\ValidateParam as ValidateParam;         //ValidateParam                 >> To validate the body form request  
use \classes\middleware\ValidateParamURL as ValidateParamURL;   //ValidateParamURL              >> To validate the query parameter url
use \classes\middleware\ApiKey as ApiKey;                       //ApiKey Middleware             >> To authorize request by using ApiKey generated by reSlim
use \classes\SimpleCache as SimpleCache;                        //SimpleCache class             >> To cache response ouput server side


    // Get module information
    $app->map(['GET','OPTIONS'],'/crud_mod/get/info/', function (Request $request, Response $response) {
        $cm = new CrudMod($this->db);
        $body = $response->getBody();
        $response = $this->cache->withEtag($response, $this->etag2hour.'-'.trim($_SERVER['REQUEST_URI'],'/'));
        $body->write($cm->viewInfo());
        return classes\Cors::modify($response,$body,200,$request);
    })->add(new ApiKey);

    // Installation 
    $app->get('/crud_mod/install/{username}/{token}', function (Request $request, Response $response) {
        $cm = new CrudMod($this->db);
        $cm->username = $request->getAttribute('username');
        $cm->token = $request->getAttribute('token');
        $body = $response->getBody();
        $body->write($cm->install());
        return classes\Cors::modify($response,$body,200);
    });

    // Uninstall (This will clear all data) 
    $app->get('/crud_mod/uninstall/{username}/{token}', function (Request $request, Response $response) {
        $cm = new CrudMod($this->db);
        $cm->username = $request->getAttribute('username');
        $cm->token = $request->getAttribute('token');
        $body = $response->getBody();
        $body->write($cm->uninstall());
        return classes\Cors::modify($response,$body,200);
    });


    //CRUD======================================================


    // POST api to create new data
    $app->post('/crud_mod/create', function (Request $request, Response $response) {
        $cm = new CrudMod($this->db);
        $datapost = $request->getParsedBody();
        $cm->lang = (empty($_GET['lang'])?$this->settings['language']:$_GET['lang']);
        $cm->username = $datapost['Username'];
        $cm->token = $datapost['Token'];
        $cm->fullname = $datapost['Fullname'];
        $cm->address = $datapost['Address'];
        $cm->telp = $datapost['Telp'];
        $cm->email = $datapost['Email'];
        $cm->website = $datapost['Website'];
        $body = $response->getBody();
        $body->write($cm->create());
        return classes\Cors::modify($response,$body,200);
    })->add(new ValidateParam('Website','0-50','url'))
        ->add(new ValidateParam('Email','0-50','email'))
        ->add(new ValidateParam('Telp','0-15','numeric'))
        ->add(new ValidateParam('Address','0-250'))
        ->add(new ValidateParam('Token','1-250','required'))
        ->add(new ValidateParam(['Username','Fullname'],'1-50','required'));


    // POST api to update data
    $app->post('/crud_mod/update', function (Request $request, Response $response) {
        $cm = new CrudMod($this->db);
        $datapost = $request->getParsedBody();
        $cm->lang = (empty($_GET['lang'])?$this->settings['language']:$_GET['lang']);
        $cm->username = $datapost['Username'];
        $cm->token = $datapost['Token'];
        $cm->fullname = $datapost['Fullname'];
        $cm->address = $datapost['Address'];
        $cm->telp = $datapost['Telp'];
        $cm->email = $datapost['Email'];
        $cm->website = $datapost['Website'];
        $cm->id = $datapost['ID'];
        $body = $response->getBody();
        $body->write($cm->update());
        return classes\Cors::modify($response,$body,200);
    })->add(new ValidateParam('Website','0-50','url'))
        ->add(new ValidateParam('Email','0-50','email'))
        ->add(new ValidateParam('Telp','0-15','numeric'))
        ->add(new ValidateParam('Address','0-250'))
        ->add(new ValidateParam('ID','1-11','numeric'))
        ->add(new ValidateParam('Token','1-250','required'))
        ->add(new ValidateParam(['Username','Fullname'],'1-50','required'));


    // POST api to delete data
    $app->post('/crud_mod/delete', function (Request $request, Response $response) {
        $cm = new CrudMod($this->db);
        $datapost = $request->getParsedBody();
        $cm->lang = (empty($_GET['lang'])?$this->settings['language']:$_GET['lang']);
        $cm->id = $datapost['ID'];
        $cm->username = $datapost['Username'];
        $cm->token = $datapost['Token'];
        $body = $response->getBody();
        $body->write($cm->delete());
        return classes\Cors::modify($response,$body,200);
    })->add(new ValidateParam('ID','1-11','numeric'))
        ->add(new ValidateParam('Token','1-250','required'))
        ->add(new ValidateParam('Username','1-50','required'));


    // GET api to show all data (index) with pagination
    $app->get('/crud_mod/index/{username}/{token}/{page}/{itemsperpage}/', function (Request $request, Response $response) {
        $cm = new CrudMod($this->db);
        $cm->lang = (empty($_GET['lang'])?$this->settings['language']:$_GET['lang']);
        $cm->search = filter_var((empty($_GET['query'])?'':$_GET['query']),FILTER_SANITIZE_STRING);
        $cm->username = $request->getAttribute('username');
        $cm->token = $request->getAttribute('token');
        $cm->page = $request->getAttribute('page');
        $cm->itemsPerPage = $request->getAttribute('itemsperpage');
        $body = $response->getBody();
        $body->write($cm->index());
        return classes\Cors::modify($response,$body,200);
    })->add(new ValidateParamURL('query'));


    // GET api to read single data
    $app->get('/crud_mod/read/{id}/{username}/{token}', function (Request $request, Response $response) {
        $cm = new CrudMod($this->db);
        $cm->lang = (empty($_GET['lang'])?$this->settings['language']:$_GET['lang']);
        $cm->username = $request->getAttribute('username');
        $cm->token = $request->getAttribute('token');
        $cm->id = $request->getAttribute('id');
        $body = $response->getBody();
        $body->write($cm->read());
        return classes\Cors::modify($response,$body,200);
    });

    // GET api to read single data for public user (include cache)
    $app->map(['GET','OPTIONS'],'/crud_mod/read/{id}/', function (Request $request, Response $response) {
        $cm = new CrudMod($this->db);
        $cm->lang = (empty($_GET['lang'])?$this->settings['language']:$_GET['lang']);
        $cm->id = $request->getAttribute('id');
        $body = $response->getBody();
        $response = $this->cache->withEtag($response, $this->etag.'-'.trim($_SERVER['REQUEST_URI'],'/'));
        if (SimpleCache::isCached(300,["apikey","lang"])){
            $datajson = SimpleCache::load(["apikey","lang"]);
        } else {
            $datajson = SimpleCache::save($cm->readPublic(),["apikey","lang"],null,300);
        }
        $body->write($datajson);
        return classes\Cors::modify($response,$body,200,$request);
    })->add(new ValidateParamURL('lang','0-2'))->add(new ApiKey);