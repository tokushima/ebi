<?php
namespace test\plugin;

class PluginTest{
    use \ebi\Plugin;

    public function abc(){
        print('abc');

        if(static::has_class_plugin('xyz')){
            static::call_class_plugin_func('xyz');
        }
    }
}