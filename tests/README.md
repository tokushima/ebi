ebi tests
====


```
cd tests
```


# Installing

## Download Composer

https://getcomposer.org/download/

```
./composer.phar
```

## Download cmdman

https://git.io/cmdman.phar

```
./cmdman.phar
```

## Download testman

https://git.io/testman.phar

```
./test/testman.phar
```



# Setup

```
php composer.phar update
php cmdman.phar ebi.Dt::dao_create_table
php cmdman.phar ebi.Dt::setup
```

# Development Server started 

```
php -S localhost:8000
```

# Test

```
php test/testman.phar
```





