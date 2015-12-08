<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2015 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

namespace think;

class Error
{
    /**
     * 自定义异常处理
     * @access public
     * @param mixed $e 异常对象
     */
    public static function appException($e)
    {
        $error = [
            'message'   => $e->getMessage(),
            'file'      => $e->getFile(),
            'line'      => $e->getLine(),
            'trace'     => $e->getTraceAsString(),
            'code'      => $e->getCode(),
        ];
        // 记录异常日志
        Log::record($error['message'], 'ERR');
        // 发送500信息
        header('HTTP/1.1 500 Internal Server Error');
        header('Status:500 Internal Server Error');
        // 输出异常页面
        self::halt($error);
    }

    /**
     * 自定义错误处理
     * @access public
     * @param int $errno 错误类型
     * @param string $errstr 错误信息
     * @param string $errfile 错误文件
     * @param int $errline 错误行数
     * @return void
     */
    public static function appError($errno, $errstr, $errfile, $errline)
    {
        $errorStr = "[{$errno}] {$errstr} {$errfile} 第 {$errline} 行.";
        switch ($errno) {
            case E_USER_ERROR:
                Log::record($errorStr, 'ERROR');
                self::halt($errorStr, $errno);
                break;
            case E_STRICT:
            case E_USER_WARNING:
            case E_USER_NOTICE:
            default:
                Log::record($errorStr, 'NOTIC');
                break;
        }
    }

    /**
     * 应用关闭处理
     * @return void
     */
    public static function appShutdown()
    {
        // 记录日志
        Log::save();
        if ($e = error_get_last()) {
            switch ($e['type']) {
                case E_ERROR:
                case E_PARSE:
                case E_CORE_ERROR:
                case E_COMPILE_ERROR:
                case E_USER_ERROR:
                    ob_end_clean();
                    self::halt($e);
                    break;
            }
        }
    }

    /**
     * 错误输出
     *
     * @param mixed $error 错误
     * @param int   $code
     *
     * @internal param int $errno 错误代码
     */
    public static function halt($error, $code = 1)
    {
        $message = is_array($error) ? $error['message'] : $error;
        $code    = is_array($error) ? $error['code'] : $code;
        $e = [];
        if (APP_DEBUG) {
            //调试模式下输出错误信息
            if (!is_array($error)) {
                $trace        = debug_backtrace();
                $e['message'] = $error;
                $e['file']    = $trace[0]['file'];
                $e['line']    = $trace[0]['line'];
                ob_start();
                debug_print_backtrace();
                $e['trace'] = ob_get_clean();
            } else {
                $e = $error;
            }
        } else {
            //否则定向到错误页面
            $error_page = Config::get('error_page');
            if (!empty($error_page)) {
                header('Location: ' . $error_page);
            } else {
                if (Config::get('show_error_msg')) {
                    $e['message'] = is_array($error) ? $error['message'] : $error;
                } else {
                    $e['message'] = Config::get('error_message');
                }
            }
        }
        // 异常信息输出监听
        Hook::listen('error_output', $e);
        // 输出异常内容
        Response::returnData($e, Config::get('default_return_type'));
        exit;
    }
}
