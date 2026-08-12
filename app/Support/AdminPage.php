<?php

declare(strict_types=1);

namespace App\Support;

use App\Foundation\Config;
use App\Http\Response;
use App\Security\Csrf;
use App\View\View;

final readonly class AdminPage
{
    public function __construct(private View $view,private Config $config,private Csrf $csrf,private Flash $flash) {}
    public function render(string $template,string $title,array $data=[],int $status=200): Response
    {
        $body=$this->view->render($template,[...$data,'title'=>$title,'appName'=>(string)$this->config->get('app.name','Frigo Sistem'),'csrfToken'=>$this->csrf->token(),'flash'=>$this->flash->pull(),'showAdminNav'=>true],'layouts/admin');
        return new Response($body,$status,['Content-Type'=>'text/html; charset=UTF-8','Cache-Control'=>'no-store, private','Pragma'=>'no-cache','X-Robots-Tag'=>'noindex, nofollow']);
    }
    public function csrfValid(mixed $token): bool{return $this->csrf->validate($token);}
    public function csrfFailure(): Response{return new Response('The request could not be verified.',419,['Content-Type'=>'text/plain; charset=UTF-8','Cache-Control'=>'no-store']);}
}
