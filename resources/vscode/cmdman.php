<?php
namespace cmdman;

class Std{
    public static function println(?string $value=null){}
    public static function println_info(?string $value=null){}
    public static function println_warning(?string $value=null){}
    public static function println_success(?string $value=null){}
    public static function println_danger(?string $value=null){}
    public static function backspace(){}
}
class Args{
    public static function value(): string{
        return '';
    }
}