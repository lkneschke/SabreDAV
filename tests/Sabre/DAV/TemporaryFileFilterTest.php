<?php

class Sabre_DAV_TemporaryFileFilterTest extends Sabre_DAV_AbstractServer {

    function setUp() {

        parent::setUp();
        $plugin = new Sabre_DAV_TemporaryFileFilterPlugin('temp/tff');
        $this->server->addPlugin($plugin);

    }

    function testPutNormal() {

        $serverVars = array(
            'REQUEST_URI'    => '/testput.txt',
            'REQUEST_METHOD' => 'PUT',
        );

        $request = new Sabre_HTTP_Request($serverVars);
        $request->setBody('Testing new file');
        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals('', $this->response->body);
        $this->assertEquals('HTTP/1.1 201 Created',$this->response->status);
        $this->assertEquals(array(),$this->response->headers);

        $this->assertEquals('Testing new file',file_get_contents($this->tempDir . '/testput.txt'));

    }

    function testPutTemp() {

        // mimicking an OS/X resource fork
        $serverVars = array(
            'REQUEST_URI'    => '/._testput.txt',
            'REQUEST_METHOD' => 'PUT',
        );

        $request = new Sabre_HTTP_Request($serverVars);
        $request->setBody('Testing new file');
        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals('', $this->response->body);
        $this->assertEquals('HTTP/1.1 201 Created',$this->response->status);
        $this->assertEquals(array(
            'X-Sabre-Temp' => 'true',
        ),$this->response->headers);

        $this->assertFalse(file_exists($this->tempDir . '/._testput.txt'),'._testput.txt should not exist in the regular file structure.');

    }

    function testPutGet() {

        // mimicking an OS/X resource fork
        $serverVars = array(
            'REQUEST_URI'    => '/._testput.txt',
            'REQUEST_METHOD' => 'PUT',
        );

        $request = new Sabre_HTTP_Request($serverVars);
        $request->setBody('Testing new file');
        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals('', $this->response->body);
        $this->assertEquals('HTTP/1.1 201 Created',$this->response->status);
        $this->assertEquals(array(
            'X-Sabre-Temp' => 'true',
        ),$this->response->headers);

        $serverVars = array(
            'REQUEST_URI'    => '/._testput.txt',
            'REQUEST_METHOD' => 'GET',
        );

        $request = new Sabre_HTTP_Request($serverVars);
        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals('HTTP/1.1 200 Ok',$this->response->status);
        $this->assertEquals(array(
            'X-Sabre-Temp' => 'true',
            'Content-Length' => 16,
            'Content-Type' => 'application/octet-stream',
        ),$this->response->headers);

        $this->assertEquals('Testing new file',stream_get_contents($this->response->body));

    }

    function testLockNonExistant() {

        mkdir($this->tempDir . '/locksdir');
        $locksBackend = new Sabre_DAV_Locks_Backend_FS($this->tempDir . '/locksdir');
        $locksPlugin = new Sabre_DAV_Locks_Plugin($locksBackend);
        $this->server->addPlugin($locksPlugin);

        // mimicking an OS/X resource fork
        $serverVars = array(
            'REQUEST_URI'    => '/._testlock.txt',
            'REQUEST_METHOD' => 'LOCK',
        );

        $request = new Sabre_HTTP_Request($serverVars);

        $request->setBody('<?xml version="1.0"?>
<D:lockinfo xmlns:D="DAV:"> 
    <D:lockscope><D:exclusive/></D:lockscope> 
    <D:locktype><D:write/></D:locktype> 
    <D:owner> 
        <D:href>http://example.org/~ejw/contact.html</D:href> 
    </D:owner> 
</D:lockinfo>');

        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals('HTTP/1.1 201 Created',$this->response->status);
        $this->assertEquals(array(
            'X-Sabre-Temp' => 'true',
            'Content-Type' => 'application/xml; charset=utf-8',
        ),$this->response->headers);
        
        $this->assertFalse(file_exists($this->tempDir . '/._testlock.txt'),'._testlock.txt should not exist in the regular file structure.');

    }

    function testPutDelete() {

        // mimicking an OS/X resource fork
        $serverVars = array(
            'REQUEST_URI'    => '/._testput.txt',
            'REQUEST_METHOD' => 'PUT',
        );

        $request = new Sabre_HTTP_Request($serverVars);
        $request->setBody('Testing new file');
        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals('', $this->response->body);
        $this->assertEquals('HTTP/1.1 201 Created',$this->response->status);
        $this->assertEquals(array(
            'X-Sabre-Temp' => 'true',
        ),$this->response->headers);

        $serverVars = array(
            'REQUEST_URI'    => '/._testput.txt',
            'REQUEST_METHOD' => 'DELETE',
        );

        $request = new Sabre_HTTP_Request($serverVars);
        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals('HTTP/1.1 204 No Content',$this->response->status);
        $this->assertEquals(array(
            'X-Sabre-Temp' => 'true',
        ),$this->response->headers);

        $this->assertEquals('',$this->response->body);

    }

    function testPutPropfind() {

        // mimicking an OS/X resource fork
        $serverVars = array(
            'REQUEST_URI'    => '/._testput.txt',
            'REQUEST_METHOD' => 'PUT',
        );

        $request = new Sabre_HTTP_Request($serverVars);
        $request->setBody('Testing new file');
        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals('', $this->response->body);
        $this->assertEquals('HTTP/1.1 201 Created',$this->response->status);
        $this->assertEquals(array(
            'X-Sabre-Temp' => 'true',
        ),$this->response->headers);

        $serverVars = array(
            'REQUEST_URI'    => '/._testput.txt',
            'REQUEST_METHOD' => 'PROPFIND',
        );

        $request = new Sabre_HTTP_Request($serverVars);
        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals('HTTP/1.1 207 Multi-Status',$this->response->status,'Incorrect status code returned. Body: ' . $this->response->body);
        $this->assertEquals(array(
            'X-Sabre-Temp' => 'true',
            'Content-Type' => 'application/xml; charset=utf-8',
        ),$this->response->headers);

        $this->markTestIncomplete('Need to verify body');

    }

}