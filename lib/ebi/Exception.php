<?php
namespace ebi;

class Exception extends \Exception{
    protected ?int $http_status = null;

    public function http_status(): ?int{
        return $this->http_status;
    }
}
