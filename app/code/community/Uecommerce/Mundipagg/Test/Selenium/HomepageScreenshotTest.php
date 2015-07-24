<?php

class Codex_Demo_Test_Selenium_HomepageScreenshotTest extends Codex_Xtest_Xtest_Unit_Frontend
{

    /**
     * As a frontend designer
     * - When open '/'
     * - And I take some screenshot
     * - Then I should check screenshots manually
     */
    public function testRenderHomepage()
    {
        $this->dispatch('/');
        $this->renderHtml('homePage', $this->getResponseBody() );
    }

}