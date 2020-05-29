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

namespace W7\App\Model\Entity;

use Illuminate\Support\Str;

class Document extends BaseModel
{
	const PRIVATE_DOCUMENT = 2;//仅有权限查看
	const PUBLIC_DOCUMENT = 1;//默认-公开项目
	const LOGIN_PREVIEW_DOCUMENT = 3;//点击链接登录后查看

	protected $table = 'document';

	/**
	 * 关联作者
	 */
	public function user()
	{
		return $this->belongsTo(User::class, 'creator_id', 'id');
	}

	/**
	 * 关联权限表中的操作人员
	 * @return \Illuminate\Database\Eloquent\Relations\HasMany
	 */
	public function operator()
	{
		return $this->hasMany(DocumentPermission::class, 'document_id', 'id');
	}

	public function getDescriptionShortAttribute()
	{
		return Str::limit(html_entity_decode($this->description), 20);
	}

	public function getIsPublicDocAttribute()
	{
		return $this->is_public == self::PUBLIC_DOCUMENT;
	}

	public function getIsPrivateDocAttribute()
	{
		return $this->is_public == self::PRIVATE_DOCUMENT;
	}

	public function getIsLoginPreviewDocAttribute()
	{
		return $this->is_public == self::LOGIN_PREVIEW_DOCUMENT;
	}
}
