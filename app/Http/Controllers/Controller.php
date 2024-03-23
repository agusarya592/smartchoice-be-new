<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use EasyRdf\RdfNamespace;
use EasyRdf\Sparql\Client;

class Controller extends BaseController
{
    public $sparql;

    function __construct()
    {
        RdfNamespace::set('rdf', 'http://www.w3.org/1999/02/22-rdf-syntax-ns#');
        RdfNamespace::set('rdfs', 'http://www.w3.org/2000/01/rdf-schema#');
        RdfNamespace::set('owl', 'http://www.w3.org/2002/07/owl#');
        RdfNamespace::set('smartphone', 'http://www.semanticweb.org/asusrog/ontologies/2024/1/untitled-ontology-26#');

        $this->sparql = new Client('http://localhost:3030/smartchoice/query');
    }
    
    public function parseData($str)
    {
        return str_replace('http://www.semanticweb.org/asusrog/ontologies/2024/1/untitled-ontology-26#', '', $str);
    }
}
