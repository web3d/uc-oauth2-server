<?php
/**
 * Demo 模仿新浪微博接口格式
 */
class OAuth2_API_Statuses {
    
    /**
     * 返回最新的200条公共微博，返回结果非完全实时
     * 
     * @param int $count
     * @return array
     */
    public function public_timeline($count = 20){
        return array('result' => array(1,2,3,4));
    }
    
    /**
     * @url GET tags/create
     * @param string $tag
     * @return boolean
     */
    public function create_tag($tag){
        return true;
    }
}