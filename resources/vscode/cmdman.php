<?php
namespace cmdman;

class Std{
    public static function println(?string $value=null): void{}
    public static function println_info(?string $value=null): void{}
    public static function println_warning(?string $value=null): void{}
    public static function println_success(?string $value=null): void{}
    public static function println_danger(?string $value=null): void{}
    public static function backspace(){}
    public static function read(string $msg, $default=null, array $choice=[], bool $multiline=false): string{
        return '';
    }
    public static function silently(string $msg, $default=null, array $choice=[], bool $multiline=false): string{
        return '';
    }
    public function p(string $msg, int $color=0): void{}
}
class Args{
    public static function value(): string{
        return '';
    }
    public static function values(): array{
        return [];
    }
	public static function opt(string $name, $default=false){
        return '';
	}
	public static function opts(string $name): array{
        return [];
	}
}

class Util{
    public static function exit_wait(): void{}
    public static function exit_error(): void{}
    public static function pctrl(callable $callback,array $data): void{}
    
}
