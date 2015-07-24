<?php
class Codex_Demo_Test_Controller_HomepageControllerTest extends Codex_Xtest_Xtest_Unit_Frontend
{

    /**
     * As Customer
     * - when I open Homepage
     * - I should see "New Products"
     */
    public function testHomePageContains()
    {
        $this->dispatch('/');

        // Checks Layout Wrapper exists
        //$this->assertLayoutBlockExists('cms.wrapper');

        // Checks page contains some content
        $this->assertContains('cms-home', $this->getResponseBody() );

    }

}