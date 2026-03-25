<?php
namespace ebi\app;

abstract class AuthenticationHandler{
    public function remember_me(\ebi\app\Request $request): bool{
        return false;
    }

    public function login_condition(\ebi\app\Request $request): bool{
        return false;
    }

    public function after_login(\ebi\app\Request $request): void{
    }

    public function get_after_vars_login(\ebi\app\Request $request): array{
        return [];
    }

    public function before_logout(\ebi\app\Request $request): void{
    }

}
