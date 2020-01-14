<?php

require EXTEND_PATH.'/org/excel/PHPExcel.php';

class Excel{

    /**
     * 写入数据到excel
     * @author:zgz
     * @param $file
     * @param $data
     * @param int $start
     * @param int $sheet_num
     * @param boolean $flag true表示文件存在也强制重新导出
     * @return PHPExcel_Writer_IWriter
     */
    public static function export_excel($template,$map,$data,$row_start=4,$sheet_num=0,$flag=false){

        $file_name = "";
        if(is_array($map)){
            foreach($map as $key=>$val){
                if($val != "" && !is_null($val) && !is_array($val)){
                    $file_name .= "_".$val;
                }
            }
        }
        if(empty($file_name)) $file_name = time();
        $temp_dlr = "temp/";
        $template_path = DOWNLOAD_PATH."template/".$template;
        $extend = strrchr($template_path, '.');  //获取上传文件的扩展名
        $template_file_name = substr($template,0,strripos($template,"."));
        $dest_file = DOWNLOAD_PATH.$temp_dlr.$template_file_name.$file_name.$extend;
        if(file_exists($dest_file)){
            $create_time = filectime($dest_file);
            if(time() - $create_time > 60*30){ //生成的文件只保留30分钟
                unlink($dest_file);
            }else{
               $flag && unlink($dest_file);
            }
        }
        if(!file_exists($dest_file)){

            copy($template_path,$dest_file);//拷贝

            $file_type = ($extend == ".xlsx") ? "Excel2007" : "Excel5";
            $objPHPExcel = null;
            $sheet = null;

            $objPHPExcel = PHPExcel_IOFactory::createReader($file_type)->load($dest_file);
            $objPHPExcel->setActiveSheetIndex($sheet_num);
            $sheet = $objPHPExcel->getActiveSheet();
            $sheet->insertNewRowBefore($row_start+1,count($data));
            $row = $row_start;
            foreach($data as $row_value){
                $col = 0;
                foreach($row_value as $col_value){
                    $sheet->setCellValueByColumnAndRow($col, $row, $col_value);
                    $col++;
                }
                $row++;
            }
            $objWriter=PHPExcel_IOFactory::createWriter($objPHPExcel, $file_type);
            //echo $file_path;exit;
            $objWriter->save($dest_file);
        }
        return $temp_dlr.$template_file_name.$file_name.$extend;
    }

    /**
     * 读取excel表数据大小
     * @param string $file 文件路径
     */
    public static function  excel_get_data_size($file){
        $sheet_num = 0;
        $extend = strrchr($file, '.');  //获取上传文件的扩展名
        $file_type = ($extend == ".xlsx") ? "Excel2007" : "Excel5";
        $objPHPExcel = PHPExcel_IOFactory::createReader($file_type)->load($file);
        $sheet = $objPHPExcel->getSheet($sheet_num);
        $total_row = $sheet->getHighestRow(); // 取得总行数
        // $total_col = $sheet->getHighestColumn(); // 取得总列数
        
        return $total_row;
    }

    /**
     * 读取excel表数据
     * @param string $file 文件路径
     * @param integer $start 从第几行开始读
     * @param integer $limit 读多少行
     * @param integer $sheet_num 第几个工作薄 默认为0
     * @return array like array( 'data' => array( ), 'keep' => false, 'start' => 0, 'progress' => 0 );
     */
    public static function excel_get_data($file, $start, $limit, $sheet_num = 0){
        $return = array('data' => array(), 'keep' => false, 'start' => 0, 'progress' => 0);
        $extend = strrchr($file, '.');  //获取上传文件的扩展名
        $file_type = ($extend == ".xlsx") ? "Excel2007" : "Excel5";
        $objPHPExcel = PHPExcel_IOFactory::createReader($file_type)->load($file);
        $sheet = $objPHPExcel->getSheet($sheet_num);
        $total_row = $sheet->getHighestRow(); // 取得总行数
        $total_col = $sheet->getHighestColumn(); // 取得总列数
        if (strlen($total_col) > 1) {
            $col_arr = range('A', 'Z');
            $str = substr($total_col, 1, 1);
            $col_arr_two = range('A', $str);
            foreach ($col_arr_two as $key => $value) {
                $col_arr_two[$key] = 'A' . $value;
            }
            $col_arr = array_merge($col_arr, $col_arr_two);
        } else {
            $col_arr = range('A', $total_col);
        }
        if ($start + $limit < $total_row) {
            $for_total = $start + $limit;
            $return['keep'] = true;
            $return['start'] = $for_total + 1;
        } else {
            $for_total = $total_row;
        }
        $finished = false;
        for ($row = $start; $row <= $for_total; $row++) {
            $arr = array();
            foreach ($col_arr as $col_name) {
                $cell_val = trim($objPHPExcel->getActiveSheet()->getCell($col_name . $row)->getValue() . '');
                if (strpos($cell_val, '#数据结尾#') === false) {
                    $arr[$col_name] = $cell_val;
                } else { // 到底结尾
                    $finished = true;
                    break;
                }
            }
            if (!$finished) {
                $return['data'][$row] = $arr;
            } else {
                $return['keep'] = false;
                break;
            }
        }
        $return['progress'] = ceil($for_total / $total_row * 100);
        return $return;
    }


}