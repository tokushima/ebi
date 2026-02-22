<?php
namespace ebi;

class Exception extends \Exception{
    protected ?int $http_status;

    public function http_status(): ?int{
        return $this->http_status;
    }
}
