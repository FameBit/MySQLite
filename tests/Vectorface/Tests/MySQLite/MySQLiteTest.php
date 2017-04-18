<?php

namespace Vectorface\Tests\MySQLite;

use InvalidArgumentException;
use PDO;
use PDOException;
use PHPUnit_Framework_TestCase;
use Vectorface\MySQLite\MySQLite;
use Vectorface\Tests\MySQLite\Util\FakePDO;

/**
 * Test MySQLite; This could be split up into individual function categories later.
 */
class MySQLiteTest extends PHPUnit_Framework_TestCase
{
    const Y_M_D_FORMAT_STRING = 'Y-m-d';

    /**
     * Test miscellaneous compatibility functions.
     */
    public function testCompatibilityFunctions()
    {
        /* Aggregate functions */
        $this->assertEquals(1 | 2 | 4, MySQLite::mysql_bit_or(1, 2, 4));

        /* Comparison functions */
        $this->assertEquals(1, MySQLite::mysql_least(1, 2, 3, 4));
        try {
            MySQLite::mysql_least();
            $this->fail("Least with no arguments is not valid");
        } catch (\InvalidArgumentException $e) {
            /* Expected */
        }
        $this->assertEquals(1, MySQLite::mysql_isnull(null));
        $this->assertEquals(0, MySQLite::mysql_isnull(4));
        $this->assertEquals(0, MySQLite::mysql_isnull(21.34));
        $this->assertEquals(0, MySQLite::mysql_isnull('words'));
        $this->assertEquals(0, MySQLite::mysql_isnull('null'));
        $this->assertEquals(0, MySQLite::mysql_isnull(false));
        $this->assertEquals(0, MySQLite::mysql_isnull(true));

        /* Flow control functions */
        $this->assertEquals("foo", MySQLite::mysql_if(true, "foo", "bar"));
        $this->assertEquals("bar", MySQLite::mysql_if(false, "foo", "bar"));

        /* Numeric functions */
        $this->assertEquals(10, MySQLite::mysql_sqrt(100));
    }

    public function testDateTimeFunctions()
    {
        $this->assertEquals(date("Y-m-d H:i:s"), MySQLite::mysql_now());
        $this->assertEquals(365, MySQLite::mysql_to_days("0000-12-31"));
        $this->assertEquals(718613, MySQLite::mysql_to_days("1967-07-01"));
        $this->assertEquals(735599, MySQLite::mysql_to_days("2014-01-01"));
        $this->assertEquals(time(), MySQLite::mysql_unix_timestamp());
        $time = time();
        $this->assertEquals($time, MySQLite::mysql_unix_timestamp(date("Y-m-d H:i:s")));

        $date1 = new \DateTime();
        $date2 = new \DateTime('2 days ago');
        $this->assertEquals(2, MySQLite::mysql_datediff($date1->format(self::Y_M_D_FORMAT_STRING), $date2->format(self::Y_M_D_FORMAT_STRING)));

        $this->assertEquals($date1->format(self::Y_M_D_FORMAT_STRING), MySQLite::mysql_curdate());
    }

    /**
     * Test that createFunctions hooks the functions into a PDO object.
     */
    public function testCreateFunctions()
    {
        $fakepdo = new FakePDO();
        $fakepdo->attributes[PDO::ATTR_DRIVER_NAME] = 'mysql';

        try {
            MySQLite::createFunctions($fakepdo);
            $this->fail("Attempt to create functions with a driver other than SQLite should fail.");
        } catch (InvalidArgumentException $e) {
            /* Expected */
        }

        $pdo = new PDO("sqlite::memory:", null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        try {
            $pdo->query("SELECT BIT_OR(1, 2)");
            $this->fail("Attempt to BIT_OR two values is expected to fail before the function is created.");
        } catch (PDOException $e) {
            /* Expected */
        }

        $this->assertTrue($pdo === MySQLite::createFunctions($pdo));
        $this->assertEquals(3, $pdo->query("SELECT BIT_OR(1, 2)")->fetch(PDO::FETCH_COLUMN));
    }

    /**
     * Test that createFunctions is able to create only a limited subset of supported functions.
     */
    public function testSelectiveCreateFunctions()
    {
        $pdo = new PDO("sqlite::memory:", null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $this->assertTrue($pdo === MySQLite::createFunctions($pdo, ['bit_or']));
        $this->assertEquals(3, $pdo->query("SELECT BIT_OR(1, 2)")->fetch(PDO::FETCH_COLUMN));
        try {
            $pdo->query("SELECT UNIX_TIMESTAMP()");
            $this->fail("UNIX_TIMESTAMP function is expected not to have been created.");
        } catch (PDOException $e) {
            /* Expected */
        }
    }

    /**
     * Test that registered functions are listed and available.
     */
    public function testGetFunctionList()
    {
        $this->assertTrue(in_array("bit_or", MySQLite::getFunctionList()));
        $this->assertTrue(in_array("unix_timestamp", MySQLite::getFunctionList()));
    }

    /**
     * Test the concat function
     */
    public function testConcat()
    {
        $expected = 'test1 test2 test4';
        $test = MySQLite::mysql_concat("test1", " ", "test2", " ", "test4");
        $this->assertEquals($expected, $test);

        $pdo = new PDO("sqlite::memory:", null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        MySQLite::createFunctions($pdo);
        $result = $pdo->query('SELECT CONCAT("test1"," ","test2"," " ,"test4")')->fetch(PDO::FETCH_COLUMN);
        $this->assertEquals($expected, $result);
    }

     /**
     * Test the rand function
     */
    public function testRand()
    {
        $pdo = new PDO("sqlite::memory:", null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        MySQLite::createFunctions($pdo);

        $pdo->exec("CREATE TABLE testing(id INT PRIMARY KEY NOT NULL)");
        $stmt = $pdo->prepare("INSERT INTO testing (id) VALUES (?)");
        for ($x = 0; $x <= 10; $x++) {
            $stmt->execute([$x]);
        }

        $results = [];
        for ($x = 0; $x < 20; $x++) {
            $results[] = $pdo->query('SELECT id FROM testing ORDER BY RAND() LIMIT 1')->fetch(PDO::FETCH_COLUMN);
        }

        $this->assertNotEquals(
            array_slice($results, 0, 10),
            array_slice($results, 10, 10)
        );
    }

    /**
     * Test ISNULL function
     */
    public function testIsNull()
    {
        $this->assertEquals(1, MySQLite::mysql_isnull(null));
        $this->assertEquals(0, MySQLite::mysql_isnull(4));
        $this->assertEquals(0, MySQLite::mysql_isnull(21.34));
        $this->assertEquals(0, MySQLite::mysql_isnull('words'));
        $this->assertEquals(0, MySQLite::mysql_isnull('null'));
        $this->assertEquals(0, MySQLite::mysql_isnull(false));
        $this->assertEquals(0, MySQLite::mysql_isnull(true));
    }
    
    /** 
    * Test GREATEST function
    */
    public function testGreatest() 
    {
        $this->assertEquals(5, MySQLite::mysql_greatest(0, 3, 5));
        $this->assertEquals('Z', MySQLite::mysql_greatest('A', 'L', 'Z'));
        try {
            MySQLite::mysql_greatest();
            $this->fail("Greater with no arguments is not valid");
        } catch (\InvalidArgumentException $e) {
            /* Expected */
        }
    }

    /**
    * Test Year function 
    */
    public function testYear()
    {
        $this->assertEquals(2014, MySQLite::mysql_year('2014-11-14 07:31:08'));
        $this->assertEquals(2016, MySQLite::mysql_year('2016-11-14 07:31:08'));
        $this->assertEquals(1990, MySQLite::mysql_year('1990-11-14 07:31:08'));
        $this->assertEquals(0, MySQLite::mysql_year(null));
    }

    /**
    * Test Month function 
    */
    public function testMonth()
    {
        $this->assertEquals(11, MySQLite::mysql_month('2014-11-14 07:31:08'));
        $this->assertEquals(1, MySQLite::mysql_month('2016-01-14 07:31:08'));
        $this->assertEquals(9, MySQLite::mysql_month('1990-09-14 07:31:08'));
        $this->assertEquals(0, MySQLite::mysql_month(null));
    }

    /**
    * Test from unix time
    */
    public function testFromUnixTime()
    {
        $this->assertEquals('2016-02-05T19:33:57+0000', MySQLite::mysql_from_unixtime(1454700837));
        $this->assertEquals('2008-10-22T18:40:37+0000', MySQLite::mysql_from_unixtime(1224700837));
    }
}
