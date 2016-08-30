
HessianPHP 2

Hessian protocol library for PHP 5+
Licensed under the MIT license

Project home: http://code.google.com/p/hessianphp/

Manuel Gómez - 2010
vegeta.ec(a)gmail.com


## 源码修改记录


### isInternalUTF8 直接返回true
2016年05月25日，详见`erp/base-support` 69bb75f360e5fdba47c0c6dc440fa6a35858e732 提交
```php
    public static function isInternalUTF8(){


        // FXIME 现在使用的都是utf8，这里直接返回true
        return true;
        $encoding = ini_get('mbstring.internal_encoding');
        if(!$encoding)
            return false;
        return $encoding == 'UTF-8';
    }
```


### fix:共享对象造成hessianRef问题（多个相同对象，除第一个外结果都为hessianRef对象）
2016年06月24日
```php
    function fillMap($index){
        if(!isset($this->refmap->classlist[$index]))
            throw new HessianParsingException("Class def index $index not found");
        $classdef = $this->refmap->classlist[$index];

        $localType = $this->typemap->getLocalType($classdef->type);
        $obj = $this->objectFactory->getObject($localType);

        $this->refmap->incReference();
        $this->refmap->objectlist[] = $obj;

        foreach($classdef->props as $prop){
            $item = $this->parse();
            if(HessianRef::isRef($item)) {
                /**
                 * fix 这里不需要，也不能加引用
                 */
                // $item = &$this->refmap->objectlist[$item->index];
                $item = $this->refmap->objectlist[$item->index];
            }
            $obj->$prop = $item;
        }

        return $obj;
    }
```

```php
// Hessian2Parser.php @typedMap @untypedMap
    // if(HessianRef::isRef($key)) $key = &$this->objectlist->reflist[$key->index];
    // if(HessianRef::isRef($value)) $value = &$this->objectlist->reflist[$value->index];

    if(HessianRef::isRef($key)) $key = $this->refmap->objectlist[$value->index] ;
    if(HessianRef::isRef($value)) $value = $this->refmap->objectlist[$value->index] ;
```


### fix:返回浮点数（double64）错误
2016年08月18日

e.g. service返回0.7，但unpack后值为 1.9035985662652E+185

原因：在paser中（Hessian2parser:203,Hessian1parser:130）unpack之前有一个是否将字节翻转的
判断，原来的判断为`HessianUtils::$littleEndian`，实际上这个静态变量默认值为null，并且在运行中
没有赋值，实际上只有在`HessianUtils::isLittleEndian()`这个方法中有赋值动作，并且这个方法的含义以
及行为就是判断是否isLittleEndian，极大可能原本就应该调用此方法做为判断依据而不是直接判断属性
`$littleEndian`，这应该又是hessianPHP一个幼稚的bug。*同理在HessianUtils有两个一样的判断，这次
未修改，因为没法验证*。

修复

```php
// Hessian2parser
    function double64($code, $num){
        $bytes = $this->read(8);
        /**
         * FIX 2016年08月18日10:01:37 修复返回浮点数出错
         * @author 炒饭
         */
        // - if(HessianUtils::$littleEndian)
        // -    $bytes = strrev($bytes);
        if(HessianUtils::isLittleEndian())
            $bytes = strrev($bytes);

        //$double = unpack("dflt", strrev($bytes));
        $double = unpack("dflt", $bytes);

        return $double['flt'];
    }
```

```php
// Hessian1parser
    function parseDouble($code, $num){
        $bytes = $this->read(8);
        /**
         * FIX 2016年08月18日10:01:37
         * @author 炒饭
         */
        // - if(HessianUtils::$littleEndian)
        // -    $bytes = strrev($bytes);
        if(HessianUtils::isLittleEndian())
            $bytes = strrev($bytes);

        //$double = unpack("dflt", strrev($bytes));
        $double = unpack("dflt", $bytes);
        return $double['flt'];
    }
```
### fix:长整形

```php
// Hessian2Writer 修改前

    function writeInt($value){
        if($this->between($value, -16, 47)){
            return pack('c', $value + 0x90);
        } else
        if($this->between($value, -2048, 2047)){
            $b0 = 0xc8 + ($value >> 8);
            $stream = pack('c', $b0);
            $stream .= pack('c', $value);
            return $stream;
        } else
        if($this->between($value, -262144, 262143)){
            $b0 = 0xd4 + ($value >> 16);
            $b1 = $value >> 8;
            $stream = pack('c', $b0);
            $stream .= pack('c', $b1);
            $stream .= pack('c', $value);
            return $stream;
        } else {
            $stream = 'I';
            $stream .= pack('c', ($value >> 24));
            $stream .= pack('c', ($value >> 16));
            $stream .= pack('c', ($value >> 8));
            $stream .= pack('c', $value);
            return $stream;
        }
    }
```

```php
// Hessian2Writer 修改后
    function writeInt($value){
        if($this->between($value, -16, 47)){
            return pack('c', $value + 0x90);
        } else
        if($this->between($value, -2048, 2047)){
            $b0 = 0xc8 + ($value >> 8);
            $stream = pack('c', $b0);
            $stream .= pack('c', $value);
            return $stream;
        } else
        if($this->between($value, -262144, 262143)){
            $b0 = 0xd4 + ($value >> 16);
            $b1 = $value >> 8;
            $stream = pack('c', $b0);
            $stream .= pack('c', $b1);
            $stream .= pack('c', $value);
            return $stream;
        } else {
            $stream = 'L';
            $stream .= pack('c', ($value >> 56));
            $stream .= pack('c', ($value >> 48));
            $stream .= pack('c', ($value >> 40));
            $stream .= pack('c', ($value >> 32));
            $stream .= pack('c', ($value >> 24));
            $stream .= pack('c', ($value >> 16));
            $stream .= pack('c', ($value >> 8));
            $stream .= pack('c', $value);
            return $stream;
        }
    }


### change:可以在对象中传递远程类型


```php
// Hessian2Writer 修改后
    function writeObjectData($value){
        $stream = '';
        $class = get_class($value);
        $index = $this->refmap->getClassIndex($class);

        if($index === false){
            $classdef = new HessianClassDef();
            $classdef->type = $class;
            if($class == 'stdClass'){
                $classdef->props = array_keys(get_object_vars($value));
            } else
                $classdef->props = array_keys(get_class_vars($class));
            $index = $this->refmap->addClassDef($classdef);
            $total = count($classdef->props);

            $type = $this->typemap->getRemoteType($class);
            $class = $type ? $type : $class;

            $stream .= 'C';
            $stream .= $this->writeString($class);
            $stream .= $this->writeInt($total);
            foreach($classdef->props as $name){
                $stream .= $this->writeString($name);
            }
        }

        if($index < 16){
            $stream .= pack('c', $index + 0x60);
        } else{
            $stream .= 'O';
            $stream .= $this->writeInt($index);
        }

        $this->refmap->objectlist[] = $value;
        $classdef = $this->refmap->classlist[$index];
        foreach($classdef->props as $key){
            $val = $value->$key;
            $stream .= $this->writeValue($val);
        }

        return $stream;
    }
```

```php
// Hessian2Writer 修改后
    function writeObjectData($value){
        $stream = '';

        $class = get_class($value);

        if (isset($value->__type) && $value->__type) {
            $__type = $value->__type;
        } else {
            $__type = $class;
        }

        $index = $this->refmap->getClassIndex($__type);

        if($index === false){

            $classdef = new HessianClassDef();
            $classdef->type = $__type;
            if($class == 'stdClass'){
                $classdef->props = array_keys(get_object_vars($value));
            } else
                $classdef->props = array_keys(get_class_vars($class));

            $classdef->props = array_filter($classdef->props, function($item) {
                return $item !== '__type';
            });

            $index = $this->refmap->addClassDef($classdef);
            $total = count($classdef->props);

            if ($__type === $class) {
                $type = $this->typemap->getRemoteType($class);
                $__type = $type ? $type : $__type;
            }

            $stream .= 'C';
            $stream .= $this->writeString($__type);
            $stream .= $this->writeInt($total);
            foreach($classdef->props as $name){
                $stream .= $this->writeString($name);
            }
        }

        if($index < 16){
            $stream .= pack('c', $index + 0x60);
        } else{
            $stream .= 'O';
            $stream .= $this->writeInt($index);
        }

        $this->refmap->objectlist[] = $value;
        $classdef = $this->refmap->classlist[$index];
        foreach($classdef->props as $key){
            $val = $value->$key;
            $stream .= $this->writeValue($val);
        }

        return $stream;
    }
```