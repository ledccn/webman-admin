<?php

namespace plugin\admin\app\controller;

use support\Request;
use support\Response;
use Throwable;

/**
 * 开发辅助相关
 */
class DevController
{
    /**
     * 表单构建
     * @param Request $request
     * @return Response
     * @throws Throwable
     */
    public function formBuild(Request $request): Response
    {
        return raw_view('dev/form-build');
    }

}
