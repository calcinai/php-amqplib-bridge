# php-amqplib-bridge

Bridge for videlalvaro/php-amqplib to expose classes compatible with PECL::AMQP

This library is a polyfill for the [PECL::AMQP Extension](http://php.net/manual/pl/book.amqp.php), using [Vadim Zaliva's php-amqplib](https://github.com/videlalvaro/php-amqplib). The PECL extension is much faster than the php implemention, from what I understand, this is due to PHP's ```stream_socket_client()```.  This library enables you to develop/deploy on systems regardless of whether the extension is available.  If the native extension is available, it will transparently be used instead of this bridge.

As a bonus, this library will provide type hints/code completion to your IDE (for the PECL extension) if it supports it.

## Setup with Composer
```
{
  "require": {
      "calcinai/php-amqplib-bridge": "0.1.*"
  }
}
```

## Usage ##

All classes, functions and exceptions should be equivelent to the native ones.  Patches are welcome for any inconsistencies.


## Examples ##

Unfortunately the documentation for the PECL extension is quite poor, but with a reasonable understanding of AMQP, you should be able to feel your way around.  There are some usage examples on the (Polish?) [man pages](http://php.net/manual/pl/book.amqp.php).

Thanks to [@pdezwart](https://github.com/pdezwart) for the method stubs, which is largely what this library is based on.
