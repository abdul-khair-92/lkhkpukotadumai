<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class UserRoles
{
    public function handle(Request $req, Closure $next)
    {
        // Izinkan user untuk mengakses form edit profil dan update profilnya sendiri
        $routeName = $req->route() ? $req->route()->getName() : '';
        if (in_array($routeName, ['user.edit', 'user.update'])) {
            $userIdParam = $req->route('user');
            if ($userIdParam == $req->user()->id) {
                $req->offsetUnset('page_menu_id');
                return $next($req);
            }
        }

        if ($req->user()->access_group()->whereHas('access_menu', fn($q) => $q->whereMenuId($req->get('page_menu_id')))->first()) {
            $req->offsetUnset('page_menu_id');
            return $next($req);
        }
        abort(403, 'Page Not Found, You Dont Have Access To This Page.', ['page' => $req->get('page_menu_id')]);
    }
}
