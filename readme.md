# HessianPHP

[![HessianPHP-version](https://img.shields.io/badge/HessianPHP-v2.0.3-green.svg)](http://code.google.com/p/hessianphp/)
![license-MIT](https://img.shields.io/badge/license-MIT-blue.svg)

Hessian是基于http的跨语言传输协议和开源项目,在阿里巴巴知名开源项目dubbo中作为底层传输工具之一.但是遗憾的是PHP实现版本在2011年起就已不在维护，在传输long、非BMP的unicode字符（emoji表情）、类型对象、double等数据存在bug.

**此项目修复了所有已知的bug,并不排除可能有其他bug,欢迎提issues.**

修改记录可能看[详情描述](https://github.com/cytle/HessianPHP/blob/master/src/Hessian/HessianPHP_v2.0.3/readme.md)

## 快速开始

### 1. 添加依赖

```shell
$ composer require cytle/HessianPHP
```

### 2. 调用

```php
// use LibHessian\HessianHelpers;
$url = 'http://xxx.xx.xx.xx:8080/dormservice/dorm';

$dormId = 218;
// 参数分别表示: 服务地址, 调用方法, 调用方法的参数列表(注意参数类型), sessionPHP配置
HessianHelpers::query($url, 'getDorm', [dormId], $options) // 返回的是result

$client = HessianHelpers::getClient($url, $options); // 获取HessianClient实例
```

## 说明
因为`PHP`64位中整数改为8字节，64位，在此中所有的负整数都以64位表示。
而在`Hessian1`协议中int都为32位，long为64位，php实现的`Hessian1`有部分代码存在此问题还未修复
，因此请谨慎使用。在64位下如有bug纯属正常。


## hessian修改记录

[详情描述](https://github.com/cytle/HessianPHP/blob/master/src/Hessian/HessianPHP_v2.0.3/readme.md)

- isInternalUTF8 直接返回true
- fix:共享对象造成hessianRef问题（多个相同对象，除第一个外结果都为hessianRef对象）
- fix:返回浮点数(double64)错误
- fix:传递长整形(long)
- change:可以在对象中传递远程类型
- fix:返回浮点数错误（写入错误）
- fix:utf8mb4造成乱码（临时方案）
- fix:long -2147483648 ~ -2048负值错误
- fix:utf8mb4 增加4字节字符解析（Hessian1也修复）
- fix:64位下，写入三位小数浮点数出错
- fix:修复非64位负整浮点数bug
- fix:修复写入double 128 -128 边际值错误
- fix:支持在错误java端下获取辅助平面字符(hessian2)

## TODO

- 错误java端下，写入辅助平面字符，问题说明:[HessianJava从U+10000到U+10FFFF的码位传输错误](https://cytle.github.io/2016/10/13/HessianJava%E4%BB%8EU+10000%E5%88%B0U+10FFFF%E7%9A%84%E7%A0%81%E4%BD%8D%E4%BC%A0%E8%BE%93%E9%94%99%E8%AF%AF/)
