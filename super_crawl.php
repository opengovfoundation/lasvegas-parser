<?php
echo "please edit this script and then run it";die();
ob_start();
//set_time_limit(0);
require_once("simple_html_dom.php");
//----database connection
include("connection.php");
//===========================URLs===================

$objDrag	=	 new drag_keywords(); 
$objDrag->business_crawl($link);


class drag_keywords{	
			
		var $html;	
			
		function  drag_keywords(){			
			$this->html  = new simple_html_dom();
		}
		function __desctruct() {
			mysql_close($link);
		}
	
	
		function business_crawl($link){
			
			$result = mysql_query("SELECT * FROM crawl_items where status_crawl = 0 order by id asc limit 1",$link);
			$num = mysql_num_rows($result);
			if($num == 0) {
				echo 'There is nothing to work on';die;
			}
			$row=mysql_fetch_array($result);
			
			$parent_url = $row['link'];
			$parent_title = $row['title'];
			$parent_parent = $row['parent'];
			$parent_id = $row['id'];
			$parent_type = $row['type'];
			$parent_title1 = $parent_title;
			if(strstr($parent_title,'ARTICLE') ||  strstr($parent_title,'article')) {
				$new_chapter_title = 'article';
			}
			else {
				$new_chapter_title = 'chapter';
			}
			
			if(strstr($parent_title,'-')) {
				$parent_title_1 = explode('-',$parent_title);
				$parent_title1 = trim($parent_title_1[1]);
				$current_title_text = $parent_title1;
				$parent_title1_code = str_replace('CHAPTER ','',$parent_title_1[0]);
				$parent_title1_code = str_replace('chapter ','',$parent_title1_code);
				$parent_title1_code = str_replace('ARTICLE ','',$parent_title1_code);
				$parent_title1_code = str_replace('DIVISION ','',$parent_title1_code);
			}
			else {
				$current_title_text = $parent_title1;
				$parent_title1_code = $parent_id;
			}
			
			if(is_numeric($parent_title1_code)) {
				$parent_chapter_id = ceil($parent_title1_code);
			}




			if($row['level_title'] != 0) {
				$new_title_identifier = $row['level_title'];
				$new_title_orderby = $row['id'];				
			}
			elseif($row['parent'] == 0) {
				$new_title_identifier = '0'.$this->alphIndexToChar($row['id']);
				$new_title_orderby = $row['id'];
			}
			else {
				$new_title_identifier = 'sub_child';
			}
			
			$parent_page_text = $row['page_text'];
			if($parent_parent != 0) {
				$result1 = mysql_query("SELECT * FROM crawl_items where id = $parent_parent",$link);
				$row1=mysql_fetch_array($result1);
				$parent_title1 = $row1['title'];
				$parent_id1 = $row1['id'];
				
				
				if($new_title_identifier == 'sub_child') {
					$new_title_orderby = $row1['id'];
					if($row1['level_title'] != 0) {
						$new_title_identifier = $row1['level_title'];
					}
					elseif($row1['parent'] == 0) {
						$new_title_identifier = '0'.$this->alphIndexToChar($row1['id']);
					} 
				}
				else {
					$new_title_identifier = $row1['id'];
					$new_title_orderby = $row1['id'];
				}
				
				if($row1['level_title'] == 0) {
					$parent_level1 = $parent_id1;
				}
				else {
					$parent_level1 = $row1['level_title'];
				}
				
				
			}
			else {
				
				$parent_id1 = $parent_id;
				$parent_level1 = $parent_id;
			
			}
						
			$content	= $this->getContents($parent_url);
			
			//$content = $parent_page_text;
			$this->html->load($content);
			$i = 0;

			
			#=============== If with links =====================
			if(strstr($content,'class="ulink2"')) {
				$parent_url = substr($parent_url,0,strpos($parent_url,'/level1'));
				foreach ($this->html->find('div[class=ulink2]') as $element){ 
						$b_link = $parent_url.$element->find('a',0)->href;
						$b_link = str_replace('../','/',$b_link);
						$b_title = trim($element->find('a',0)->innertext);
						$b_title = mb_convert_encoding($b_title,"EUC-JP", "auto");
						$b_title = str_replace('?', '', $b_title);

						
						if(!empty($b_link) && !empty($b_title)) {
							$sql = "INSERT INTO crawl_items 
										SET link = '".mysql_real_escape_string($b_link)."', 
										title = '".mysql_real_escape_string($b_title)."', 
										type = 2,
										parent = $parent_id
									";
							mysql_query($sql,$link);
							$i++;
						}
				
				
				}	

			}
			
			
			
			
			#=============== If table and H1 page =====================
			elseif(strstr($content,'<h1>') && strstr($content,'<table')) {			
			
			$section_char = 'A';
			$section_number = 1;
			$data_to_write = $data_to_write_final = '';
			$open_section_tag = $open_sub_section_tag = 0;
			$h1_text = $this->html->find('h1',0)->innertext;
			
			if(!empty($h1_text)) {
				$data_to_write_this = strip_tags($h1_text);
				$data_to_write .= '<section prefix="'.$section_number.'">'.$data_to_write_this;
				$section_number++;$open_section_tag = 1;
			}
			
			foreach ($this->html->find('p') as $element){ 
						
						#===== Loop variables ====						
						$b_class = $element->class;
						$p_innertext =  $element->innertext;
						
						if(!strstr($p_innertext,'<table')) {
							// New section
							$data_to_write_this = strip_tags($p_innertext);
							if(empty($data_to_write_this) || $data_to_write_this == '&nbsp;') {continue;}
							if($open_sub_section_tag == 1) { $data_to_write .= '</section>'; $open_sub_section_tag = 0;  }
							$data_to_write .= '<section prefix="'.$section_char.'">'.$data_to_write_this;
							$open_sub_section_tag = 1;
							$section_char++;
							
						}
						else {
							
							$starting_text = substr($p_innertext,0,strpos($p_innertext,'<!--'));
							if(!empty($starting_text)) {
								$data_to_write .= '<section prefix="'.$section_char.'">'.$starting_text.'</section>';
								$section_char++;
							}
							
							$p_innertext2 = $element->find('table',0)->innertext;
							$pattern = '/(<colgroup)(.*?)(<\/colgroup>)/si';
							$p_innertext2 = preg_replace($pattern, '', $p_innertext2);
							$p_innertext2 = str_replace('</colgroup>', '', $p_innertext2);
							$p_innertext2 = str_replace('<tbody>', '', $p_innertext2);
							$p_innertext2 = str_replace('</tbody>', '', $p_innertext2);
							
							$p_innertext2 = '<table>'.$p_innertext2.'</table>';
							if($open_sub_section_tag == 1) {$data_to_write .= '</section>'; $open_sub_section_tag =0;}
							$data_to_write .= '<section prefix="'.$section_char.'" type="table">'.$p_innertext2.'</section>';
							$section_char++;
						}						
						
			}
			
			
			#===== All crawling done, now finalizing data
			if($open_sub_section_tag == 1) {$data_to_write .= '</section>';}
			if($open_section_tag == 1) {$data_to_write .= '</section>';}
			
			
			if(!empty($data_to_write)) {
				$meta2 = '';
				if($parent_type == 2) {
					$meta2 = '<unit label="'.$new_chapter_title.'" identifier="2.02" order_by="2.02" level="2">'.$parent_title1.'</unit>';
					$section_number_final = str_pad($parent_id1, 2, '0', STR_PAD_LEFT); 
				}
				else {
					$section_number_final = '01';
				}
				$data_to_write_final .= '
				<?xml version="1.0" encoding="utf-8"?>
				<law>
					<structure>
						<unit label="title" identifier="'.$new_title_identifier.'" order_by="'.$new_title_orderby.'" level="1">'.$parent_title1.'</unit>
						'.$meta2.'
					</structure>
					<section_number>'.$parent_id.'.'.$section_number_final.'</section_number>
					<catch_line>'.$parent_title1.'</catch_line>
					<order_by>00000000009104318.'.$parent_id1.'</order_by>
					'.$data_to_write.'</law>';
	
	
			}
			//================================================;
			echo $data_to_write_final;
			//================================================;
			
			
			}
			
			
			
			
			#=============== If section page =====================
			elseif(strstr($content,'class="sec"') && strstr($content,'class="seclink"')) { 
			
			
			$section_char = 'A';
			$section_number = 1;
			$data_to_write = '';
			$data_to_write_final = array();
			$open_section_tag = $open_sub_section_tag = 0;
			
			
			$section_data_in_array = array();
			
			
			
			
			
			
			$current_section_index = 0;
			$this_incr_variable = 0;
			foreach ($this->html->find('p') as $element){ 
						
						#===== Loop variables ====						
						$b_class = $element->class;
						
						
						
						
						$p_innertext =  $element->innertext;
						
						
						if($b_class == 'seclink' ) {
							continue;
						}
						elseif(strstr($b_class,'incr') ) { 
							if($open_sub_section_tag == 1) {$section_data_in_array[$current_section_index]['text'] .= '</section>'; $open_sub_section_tag = 0;}
							$data_to_write_this = trim(strip_tags($p_innertext));
							$data_to_write_this = str_replace('(','',$data_to_write_this);
							$data_to_write_this = str_replace(')','',$data_to_write_this);
							$this_incr_variable = 1;
							if(!empty($data_to_write_this)) {
								$this_incr_variable_val = $data_to_write_this;
							} else {
								$this_incr_variable_val = '';
							}
							
							
						}
						elseif($b_class == 'sec') {
							// New section	
							
							
							
							$section_char = 'A';
							$section_number = 1;
							$this_incr_variable = 0;
									
							$current_section_index ++;
							$section_data_in_array[$current_section_index]['meta'] = $section_data_in_array[$current_section_index]['text'] = $section_data_in_array[$current_section_index]['history'] = $section_data_in_array[$current_section_index]['parent_id'] = '';
							
							
							
							$data_to_write_this = trim(strip_tags($p_innertext));
							$number_of_this =  substr($p_innertext,0,strpos($p_innertext,'<xs:'));
							$number_of_this = trim(str_replace('Sec.','',$number_of_this));
							
							
							$catch_line_this = str_replace('Sec.','',$data_to_write_this);
							$catch_line_this = str_replace($number_of_this,'',$catch_line_this);
							$catch_line_this = trim(str_replace('—',' - ',$catch_line_this));
							
							
							
			
							if($open_section_tag == 1) { 
									if($open_sub_section_tag == 1) {//$section_data_in_array[$current_section_index]['text'] .= '</section>';
									}
									//$section_data_in_array[$current_section_index]['text'] .= '</section>'; 
									$section_char = 'A'; 
									$open_section_tag = 0; 
									$open_sub_section_tag = 0;
							}
							//$section_data_in_array[$current_section_index]['text'] .= '<section prefix="'.$section_number.'">'.$data_to_write_this;
							$open_section_tag = 1;
							$section_number++;
							
							
							
							
							
							$meta2 = '';
							if($parent_type == 2) {
								$meta2 = '<unit label="'.$new_chapter_title.'" identifier="'.$parent_title1_code.'" order_by="'.$parent_title1_code.'" level="2">'.$current_title_text.'</unit>';
								$section_number_final = str_pad($parent_id1, 2, '0', STR_PAD_LEFT); 
							}
							else {
								$section_number_final = '01';
							}
							$section_data_in_array[$current_section_index]['meta'] .= '
							<?xml version="1.0" encoding="utf-8"?>
							<law>
								<structure>
									<unit label="title" identifier="'.$new_title_identifier.'" order_by="'.$new_title_orderby.'" level="1">'.$parent_title1.'</unit>
									'.$meta2.'
								</structure>
								<section_number>'.$number_of_this.'</section_number>
								<catch_line>'.$catch_line_this.'</catch_line>
								<order_by>'.$number_of_this.'</order_by>'
								;
								
							
							$section_data_in_array[$current_section_index]['parent_id'] = $number_of_this;
							
							
							
							
							
							
						}
						elseif($b_class == 'historynote') {
							if($open_sub_section_tag == 1) {$section_data_in_array[$current_section_index]['text'] .= '</section>'; $open_sub_section_tag = 0;}
							$data_to_write_this = strip_tags($p_innertext);
							$data_to_write_this = str_replace('<!--Comment Text (parent not a footnote)-->(','',$data_to_write_this);
							$data_to_write_this = str_replace('§','�',$data_to_write_this);
							$section_data_in_array[$current_section_index]['history'] = '<history>'.$data_to_write_this.'</history>';
						
						}
						
						else {							
							$data_to_write_this = strip_tags($p_innertext);
							if($open_sub_section_tag == 1) { $section_data_in_array[$current_section_index]['text'] .= '</section>'; $open_sub_section_tag = 0;  }
							
						
							
							if($this_incr_variable == 1 && !empty($this_incr_variable_val) ) { $incr_to_put = $this_incr_variable_val;}else{ $incr_to_put = $section_char;}
							
							$section_data_in_array[$current_section_index]['text'] .= '<section prefix="'.$incr_to_put.'">'.$data_to_write_this;
							$open_sub_section_tag = 1;
							$section_char++;						
						}						
						
			}
			
	
			#===== All crawling done, now finalizing data
			if($open_sub_section_tag == 1) {$section_data_in_array[$current_section_index]['text'] .= '</section>';}
			if($open_section_tag == 1) {$section_data_in_array[$current_section_index]['text'] .= '</section>';}
			
			
			
			//print_r($section_data_in_array);
			//================================================;
			$data_to_write_final = $section_data_in_array;
			//================================================;
			//echo 'DONE';die;
			
			}
			
			
			#=============== If text page =====================
			else {
			
			
			$section_char = 'A';
			$section_number = 1;
			$data_to_write = $data_to_write_final = '';
			$open_section_tag = $open_sub_section_tag = 0;
			
			foreach ($this->html->find('p') as $element){ 
						
						#===== Loop variables ====						
						$b_class = $element->class;
						$p_innertext =  $element->innertext;
						
						
						
						if($b_class == 'b'){
							continue;
						}
						elseif(strstr($b_class,'bc')) {
							// New section							
							if(strstr($p_innertext,'____')) {
							continue;
							}
							$data_to_write_this = strip_tags($p_innertext);
							if($open_section_tag == 1) { 
									if($open_sub_section_tag == 1) {$data_to_write .= '</section>';}
									$data_to_write .= '</section>'; $section_char = 'A'; $open_section_tag = 0; $open_sub_section_tag = 0;
							}
							$data_to_write .= '<section prefix="'.$section_number.'">'.$data_to_write_this;
							$open_section_tag = 1;
							$section_number++;
							
						}
						else {
						
						
							if(!strstr($p_innertext,'<p')) {
								
								$data_to_write_this = strip_tags($p_innertext);
								if($open_sub_section_tag == 1) { $data_to_write .= '</section>'; $open_sub_section_tag = 0;  }
								$data_to_write .= '<section prefix="'.$section_char.'">'.$data_to_write_this;
								$open_sub_section_tag = 1;
								$section_char++;
							}
						}						
						
			}
			
			
			#===== All crawling done, now finalizing data
			if($open_sub_section_tag == 1) {$data_to_write .= '</section>';}
			if($open_section_tag == 1) {$data_to_write .= '</section>';}
			
			
			if(!empty($data_to_write)) {
				$meta2 = '';
				if($parent_type == 2) {
					$meta2 = '<unit label="'.$new_chapter_title.'" identifier="2.02" order_by="2.02" level="2">'.$parent_title1.'</unit>';
					$section_number_final = str_pad($parent_id1, 2, '0', STR_PAD_LEFT); 
				}
				else {
					$section_number_final = '01';
				}
				$data_to_write_final .= '
				<?xml version="1.0" encoding="utf-8"?>
				<law>
					<structure>
						<unit label="title" identifier="'.$new_title_identifier.'" order_by="'.$new_title_orderby.'" level="1">'.$parent_title1.'</unit>
						'.$meta2.'
					</structure>
					<section_number>'.$parent_id.'.'.$section_number_final.'</section_number>
					<catch_line>'.$parent_title.'</catch_line>
					<order_by>'.$parent_id1.'</order_by>
					'.$data_to_write.'</law>';
	
	
			}
			//================================================;
			echo $data_to_write_final;
			//================================================;
			
			
			}
			
			
			#=============== Writing file =====================
			$this->html->clear();
			if(!empty($data_to_write_final)) {
				
				
				
				if(is_array($data_to_write_final)) {
					for($ff = 1; $ff <= count($data_to_write_final); $ff++) {
					
					//echo $data_to_write_final[$ff]['parent_id'];echo '<hr>';
							$file = 'files/'.$data_to_write_final[$ff]['parent_id'].'.xml';
							$arr_data = $data_to_write_final[$ff]['meta'].'<text>'.$data_to_write_final[$ff]['text'].'</text>'.$data_to_write_final[$ff]['history'].'</law>'; 
							file_put_contents($file, $arr_data);		
					}
				}
				else {
					$file = 'files/'.$parent_id.'.xml';
					file_put_contents($file, $data_to_write_final);
				}
				
				
			}
			
			
			echo $i.' Records inserted';			
			#================ Updating parent link =====================
			$sql = "UPDATE crawl_items set 
						status_crawl = 1,  
						page_text = '".mysql_real_escape_string($content)."'
					WHERE id = $parent_id";
			mysql_query($sql,$link);
		}

		
		function getContents($URL) {
			$ch = curl_init();	// Initialize a CURL session.
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);  // Return Page contents.
			curl_setopt($ch, CURLOPT_URL, $URL);  // Pass URL as parameter.
			//curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);  // Pass URL as parameter.
			curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,10);
			curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.0)");
			curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
			curl_setopt($ch, CURLOPT_ENCODING, "gzip,deflate");
			curl_setopt($ch, CURLOPT_HTTPHEADER, array("Accept"=>"image/gif, image/x-xbitmap, image/jpeg, image/pjpeg, application/x-shockwave-flash, */*","Accept-Language"=>"en-us"));
			curl_setopt($ch, CURLOPT_HEADER, 1);
			$Result = curl_exec($ch);  // grab URL and pass it to the variable.
			curl_close($ch);  // close curl resource, and free up system resources.
			return $Result;
		}
		function alphIndexToChar($index)
		{
		   return ($index > 0 && $index < 27) ? chr($index + 64) : false;
		}

	
	} // end of class 
	
	/*   <META HTTP-EQUIV="REFRESH" CONTENT="<?=rand(5, 10)?>">    */
?>
<META HTTP-EQUIV="REFRESH" CONTENT="<?=rand(5, 10)?>">
