<?php
namespace ebi\flow;

abstract class AuthenticationHandler{
    public function remember_me(\ebi\flow\Request $request): bool{
        return false;
    }

    public function login_condition(\ebi\flow\Request $request): bool{
        return false;
    }

    public function after_login(\ebi\flow\Request $request): void{
    }

    public function get_after_vars_login(\ebi\flow\Request $request): array{
        return [];
    }

    public function before_logout(\ebi\flow\Request $request): void{
    }

}
