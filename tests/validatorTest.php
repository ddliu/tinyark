<?php
/**
 * Tinyark Framework
 *
 * @link http://github.com/ddliu/tinyark
 * @copyright  Liu Dong (http://codecent.com)
 * @license MIT
 */

class ValidatorTest extends PHPUnit_Framework_TestCase{

    public function testRules()
    {
        $successData = array(
            'required' => 'hello',
            'in' => 'dog',
            'int' => '123',
            'min' => '20',
            'max' => '30',
            'between' => '40',
            'min_length' => 'abcdefg',
            'max_length' => 'abcdefgh',
            'between_length' => 'abcdefg',
            'length' => '123456',
            'regexp' => 'abc123',
            'email' => 'hello@example.com',
            'date' => '2013-12-1',
            'date_format' => '2013-5-12 12:00:00',
            'date_before' => '2012-5-5',
            'date_after' => '2012-5-6',
            'url' => 'http://google.com/search',
            'alpha' => 'helloWorld',
            'alpha_num' => 'hello123',
            'alpha_dash' => 'hello-123_',
            'original' => 'awesome',
            'same' => 'awesome',
            'different' => 'awful',
        );
        $failData = array(
            'required' => '',
            'in' => 'tiger',
            'int' => 'x123',
            'min' => '1',
            'max' => '100',
            'between' => '60',
            'min_length' => 'ab',
            'max_length' => 'abcdefghijklmnopq',
            'between_length' => 'abc',
            'length' => '12345',
            'regexp' => '123abc',
            'email' => 'hello.com',
            'date' => '33',
            'date_format' => '5/12/2013 12:00:00',
            'date_before' => '2013-3-3',
            'date_after' => '2011-9-9',
            'url' => 'example.com/index.html',
            'alpha' => 'hello123',
            'alpha_num' => 'hello, world',
            'alpha_dash' => 'hello, world',
            'original' => 'awesome',
            'same' => 'awful',
            'different' => 'awesome',

        );

        $rules = array(
            'required' => 'required',
            'in' => 'in:cat,dog,wolf',
            'int' => 'int',
            'min' => 'min:10',
            'max' => 'max:50',
            'between' => 'between:10,50',
            'min_length' => 'min_length:5',
            'max_length' => 'max_length:10',
            'between_length' => 'between_length:5,10',
            'length' => 'length:6',
            'regexp' => 'regexp:/^[a-z]+\d+$/',
            'email' => 'email',
            'date' => 'date',
            'date_format' => 'date_format:Y-m-d H:i:s',
            'date_before' => 'date_before:2013-1-1',
            'date_after' => 'date_after:2012-1-1',
            'url' => 'url',
            'alpha' => 'alpha',
            'alpha_num' => 'alpha_num',
            'alpha_dash' => 'alpha_dash',
            'same' => 'same:original',
            'different' => 'different:original',
        );

        foreach ($rules as $key => $value) {
            // success
            $validator = new ArkValidator($successData, array(
                $key => $value
            ));

            $this->assertTrue($validator->valid(), "validate ".$key);

            $validator->mustValid();
            $this->assertEquals(count($validator->getErrors()->all()), 0);

            // fail
            $validator = new ArkValidator($failData, array(
                $key => $value
            ));

            $this->assertEquals($validator->valid(), false);

            try {
                $validator->mustValid();
                throw new Exception("mustValid should throw an exception", 1);
            } catch (ArkValidatorException $e) {
                $this->assertEquals(count($e->getErrors()->all()), 1);
            }
        }
    }

    public function testMixed()
    {
        $validator = new ArkValidator(array(
            'username' => 'hello',
            'password' => '12qeer311',
            'email' => 'hello@example.com',
            'repeat_password' => '12qeer311',
            'gender' => 'm',
            'age' => '30',
        ), array(
            'username' => 'required|alpha_dash',
            'password' => 'required|between_length:6,20',
            'email' => 'required|email',
            'repeat_password' => 'required|same:password',
            'gender' => 'required|in:m,f',
            'age' => 'required|int|between:13,100'
        ));

        $this->assertTrue($validator->valid());

        $validator = new ArkValidator(array(
            'username' => 'hello',
            'password' => '12qeer311',
            'email' => 'hello@example.com',
            'repeat_password' => '12qeer311',
            'gender' => 't',
            'age' => 'ttt',
        ), array(
            'username' => 'required|alpha_dash',
            'password' => 'required|between_length:6,20',
            'email' => 'required|email',
            'repeat_password' => 'required|same:password',
            'gender' => 'required|in:m,f',
            'age' => 'required|int|between:13,100'
        ));

        $errors = $validator->getErrors();

        $this->assertTrue($errors->has('gender'));
        $this->assertTrue($errors->has('age'));
        $this->assertContains('gender', $errors->first('gender'));
        $this->assertContains('age', $errors->first('age'));
    }
}