# Installation and Requirements

Implementing this library inside your existing project is simple.

Here is an example of our boilerplate repository that incorporates this library. To see the detailed implementation and how it works in action check the Eightshift Boilerplate repo.

**To incorporate this lib inside your project you need to have:**
* [Composer](https://getcomposer.org/) installed in your project.

**Implementation process:**
1. Run the following command for installation.

```bash
composer require infinum/eightshift-libs
```

2. You need to have autoloader installed and required in the project.

3. One main project entry point must extend [eightshift libs main entry point file](https://github.com/infinum/eightshift-libs/blob/develop/src/class-main.php). Here you can find an [example](https://github.com/infinum/eightshift-boilerplate/blob/develop/src/class-main.php) how we did it.

4. Also, you must run that main class using function.php in your theme or plugin main entry point file. Here you can find an [example](https://github.com/infinum/eightshift-boilerplate/blob/develop/functions.php) how we did it.

5. Next thing is to extend class-config.php just like you did with the main class. Here you can find an [example](https://github.com/infinum/eightshift-boilerplate/blob/develop/src/class-config.php) how we did it.

6. You are good to go. Just run `composer dump-autoload` and start extending all our features.
