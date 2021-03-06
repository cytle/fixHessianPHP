<?php

use LibHessian\HessianHelpers;

/**
* 测试
*/
class JavaBaseTestCases extends TestCase
{
    var $version = 2;
    var $options;

    // 是否为64位
    var $isOn64bitsSystem = PHP_INT_SIZE === 8;
    var $url = 'http://192.168.30.161:8088/phptest/phpApi';


    // java 数据边界
    public $intBoundary = [
        'max'         => 2147483647,
        'min'         => -2147483648,
        '127'         => 127,
        '128'         => 128,
        '255'         => 255,
        '256'         => 256,
        '2047'        => 2047,
        '2048'        => 2048,
        '32767'       => 32767,
        '32768'       => 32768,
        '65535'       => 65535,
        '65536'       => 65536,
        '262144'      => 262144,
        '262143'      => 262143,
        '-127'        => -127,
        '-128'        => -128,
        '-255'        => -255,
        '-256'        => -256,
        '-2047'       => -2047,
        '-2048'       => -2048,
        '-32767'      => -32767,
        '-32768'      => -32768,
        '-65535'      => -65535,
        '-65536'      => -65536,
        '-262144'     => -262144,
        '-262143'     => -262143,
        '2147483647'  => 2147483647,
        '-2147483647' => -2147483647,
        '-2147483648' => -2147483648,
    ];

    public $longBoundary = [
        'max'              => 9223372036854775807,
        'min'              => -9223372036854775808,
        '2147483648'       => 2147483648,
        '4294967295'       => 4294967295,
        '-4294967295'      => -4294967295,
        '5124567855432488' => 5124567855432488,
        '0x100000000'      => 0x100000000,
    ];

    public function testInt()
    {
        foreach ($this->intBoundary as $key => $value) {
            $this->assertVaule($value, 'intNum', "testInt ${key}");
        }
    }

    public function testLong()
    {
        $list = array_unique(array_merge(
            array_values($this->longBoundary),
            array_values($this->intBoundary)
        ));

        foreach ($list as $key => $value) {
            $this->assertVaule($value, 'longNum', "testLong ${key}");
        }
    }

    public function testCreateLong()
    {
        $list = array_unique(array_merge(
            array_values($this->longBoundary),
            array_values($this->intBoundary)
        ));

        foreach ($list as $key => $value) {
            $testModel = [
                'longNum' => HessianHelpers::createLong($value)
            ];

            $data = $this->query($testModel);

            $this->assertEquals($value, $data->longNum);
        }
    }

    public function testIntList()
    {
        $list = array_values($this->intBoundary);

        $a = $this->intBoundary['max'];

        while ($a > 1) {
            $a = intval($a / 13);
            array_unshift($list, $a);
            $list[] = -1 * $a;
        }

        $testModel = [
            'intList' => $list,
        ];

        $intList = $this->query($testModel)->intList;

        foreach ($list as $key => $value) {
            $this->assertEquals($value, $intList[$key]);
        }
    }


    public function testLongList()
    {
        $list = array_merge(
            array_values($this->longBoundary),
            array_values($this->intBoundary)
        );

        $a = $this->longBoundary['max'];

        while ($a > 1) {
            $a = intval($a / 13);
            array_unshift($list, $a);
            $list[] = -1 * $a;
        }

        $testModel = [
            'longList' => ($list),
        ];

        $longList = $this->query($testModel)->longList;


        foreach ($list as $key => $value) {
            $this->assertEquals($value, $longList[$key]);
        }
    }

    public function testDoubleList()
    {
        // 0点边际值
        $list = array_merge(
            array_values($this->longBoundary),
            array_values($this->intBoundary)
        );

        $list[] = 0;

        // 以13为间隔测试
        $a = $this->longBoundary['max'];

        while ($a > 1) {
            $a = $a / 13;
            array_unshift($list, $a);
            $list[] = -1 * $a;
        }

        $list = array_map(function ($v) {
            return floatval($v);
        }, $list);

        $testModel = [
            'doubleList' => $list
        ];

        $doubleList = $this->query($testModel)->doubleList;

        foreach ($list as $key => $value) {
            $this->assertEquals($value, $doubleList[$key]);
        }
    }

    public function testDouble()
    {
        $this->assertVaule(127.0, 'doubleNum', "testDouble");
        $this->assertVaule(-128.0, 'doubleNum', "testDouble");

        $this->assertVaule(123.0, 'doubleNum', "testDouble");

        $this->assertVaule(5124567855432488.0, 'doubleNum', "testDouble");
        $this->assertVaule(-126, 'doubleNum', "testDouble");
        $this->assertVaule(-92.0, 'doubleNum', "testDouble");
    }

    public function testFloat()
    {
        $this->assertVaule(1223.0, 'doubleNum', "testFloat");
    }

    public function testIntMap()
    {
        $value = [
            'asd' => 123
        ];
        $testModel = [
            'intMap' => (object) $value
        ];

        $actual = $this->query($testModel)->intMap;

        $this->assertArraySubset($value, $actual);
    }

    public function testString()
    {
        // TODO 写Emoji
        $this->assertVaule('12ACÁ。哈哈', 'str', "testString");

    }


    protected function assertVaule($value, $name, $message = '')
    {
        $testModel = [
            $name => $value
        ];
        $actual = $this->queryItem($value, $name)->{$name};

        $this->assertEquals($value, $actual, $message);
    }

    protected function queryItem($value, $name)
    {
        $testModel = [
            $name => $value
        ];
        $testModel = $this->query($testModel);

        return $testModel;
    }

    protected function query(array $testModel)
    {
        return HessianHelpers::query($this->url, 'test', [ new TestModel($testModel) ], [
            'version' => $this->version
        ]);
    }

    // 通过 queryJson 渠道获取数据
    protected function queryJsonItem($value, $name)
    {
        $testModel = [
            $name => $value
        ];
        $testModel = $this->queryJson($testModel);

        return $testModel;
    }

    // 查询，获取的数据为json字符串
    protected function queryJson(array $testModel)
    {
        $json = HessianHelpers::query($this->url, 'toString', [ new TestModel($testModel) ], [
            'version' => $this->version
        ]);

        return json_decode($json);
    }

}

/**
*
*/
class TestModel
{
    public $str;
    public $longNum;
    public $intNum;
    public $shortNum;
    public $byteNum;
    public $bool;
    public $doubleNum;
    public $floatNum;
    public $date;
    public $orderStatus;
    public $longmap;
    public $intMap;
    public $shortMap;
    public $byteMap;
    public $booleanMap;
    public $doubleMap;
    public $floatMap;
    public $dateMap;
    public $enumMap;
    public $longList;
    public $intList;
    public $shortList;
    public $byteList;
    public $boolList;
    public $doubleList;
    public $floatList;
    public $dateList;
    public $enumList;
    public $strlist;

    function __construct(array $data)
    {
        foreach ($data as $key => $value) {
            $this->{$key} = $value;
        }
    }
}

