<?php

/**
 * WeEngine Document System
 *
 * (c) We7Team 2019 <https://www.w7.cc>
 *
 * This is not a free software
 * Using it under the license terms
 * visited https://www.w7.cc for more details
 */

namespace W7\App\Model\Logic\Document\ChapterApi;

use W7\App\Model\Entity\Document\ChapterApiParam;

//返回演示数据demo
class ChapterDemoLogic extends ChapterCommonLogic
{
	protected $chapterId;

	public function __construct($chapterId)
	{
		$this->chapterId = $chapterId;
	}

	//type==1 array 2键值对  3键值对数组 $locationType 1请求2 响应
	public function getChapterDemo($locationType, $type = 1, $locationList = [])
	{
		$chapterId = $this->chapterId;
		if ($locationType == 2) {
			$locationList = array_keys($this->reponseIds());
		} elseif ($locationType == 0) {
			//使用参数locationList
		} else {
			$locationList = array_keys($this->requestIds());
		}
		$chapterList = ChapterApiParam::query()->where('chapter_id', $chapterId)->where('parent_id', 0)->whereIn('location', $locationList)->get();
		$data = $this->getChapterDemoChildrenArray($chapterList, '');
		if (in_array($type, [2, 3])) {
			//需要转键值对
			return [
				'data' => $this->getArrayToKeyValue($data['data'], $type),
				'descriptionData' => $this->getArrayToKeyValue($data['descriptionData'], $type),
			];
		}
		//data数组结构，descriptionData数据结构对应描述
		return $data;
	}

	//导出-2键值对字符串，3键值对数组
	public function getArrayToKeyValue($data, $type)
	{
		$str = http_build_query($data);
		if ($type == 3) {
			//键值对数组
			$info1 = explode('&', $str);
			$reply = [];
			foreach ($info1 as $key => $val) {
				$info2 = explode('=', $val);
				$name = urldecode($info2[0]);
				$value = urldecode(str_replace($info2[0] . '=', '', $val));
				$reply[$name] = $value;
			}
			return $reply;
		} else {
			//纯键值对
			$str = str_replace('&', "\n", $str);
			$str = str_replace('=', ':', $str);
			return urldecode($str);
		}
	}

	public function getChapterDemoChildrenArray($listChildren, $defaultValue = '')
	{
		if ($this->isJson($defaultValue)) {
			//如果是json
			$defaultValueList = json_decode($defaultValue, true);
		} else {
			$defaultValueList = [];
		}
		$data = [];
		$descriptionData = [];
		$i = 0;
		foreach ($listChildren as $key => $val) {
			$defaultValue = '';
			if ($val->default_value) {
				$defaultValue = $val->default_value;
			} else {
				if (isset($defaultValueList[$i])) {
					$defaultValue = $defaultValueList[$i];
				}
			}
			$description = $val->description;

			if (in_array($val->type, [ChapterApiParam::TYPE_OBJECT, ChapterApiParam::TYPE_ARRAY])) {
				//如果里面还是数组或者对象
				$listChildrenSun = ChapterApiParam::query()->where('chapter_id', $val->chapter_id)
					->where('parent_id', $val->id)->get();
				if (count($listChildrenSun) > 0) {
					if (is_numeric($val->rule) && ($val->rule > 1)) {
						//如果是多维数组
						$chapterDemoChildren = $this->getChapterDemoChildrenArray($listChildrenSun, '');
						$data[$val->name][] = $chapterDemoChildren['data'];
						$descriptionData[$val->name][] = $chapterDemoChildren['descriptionData'];
					} else {
						$chapterDemoChildren = $this->getChapterDemoChildrenArray($listChildrenSun, '');
						$data[$val->name] = $chapterDemoChildren['data'];
						$descriptionData[$val->name] = $chapterDemoChildren['descriptionData'];
					}
				} else {
					//没有子类
					if ($this->isJson($defaultValue)) {
						//如果是json
						$defaultValueList = json_decode($defaultValue, true);
						$defaultValue = $defaultValueList[0];
					}
					$data[$val->name] = $defaultValue;
					$descriptionData[$val->name] = $description;
				}
			} else {
				if ($val->name) {
					//对象
					$data[$val->name] = $defaultValue;
					$descriptionData[$val->name] = $description;
				} else {
					//数字键值
					$data[] = $defaultValue;
					$descriptionData[] = $description;
				}
			}
		}
		return ['data' => $data, 'descriptionData' => $descriptionData];
	}
}
