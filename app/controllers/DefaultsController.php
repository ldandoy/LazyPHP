<?php

namespace app\controllers;

use system\Controller;
use system\Query;
use app\models\Article;

class DefaultsController extends Controller
{
    public function indexAction()
    {
        $articles = Article::findAll();

        $this->render('index', array(
            'articles'  => $articles,
            'titre'     => 'articles'
        ));
    }
}