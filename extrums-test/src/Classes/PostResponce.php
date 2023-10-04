<?php

namespace ExtrumsTest\Classes;

use WP_Post;

class PostResponce
{
    private $id;
    private $title;
    private $content;
    private $meta_title;
    private $meta_desc;

    public function __construct(WP_Post $post) {
        $this->id = $post->ID;
        $this->title = $post->post_title;
        $this->content = $post->post_content;
        $this->meta_title = $post->meta_title ?? '';
        $this->meta_desc = $post->meta_desc ?? '';
    }

    private function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'content' => $this->content,
            'meta_title' => $this->meta_title,
            'meta_desc' => $this->meta_desc,
        ];
    }

    public static function make(WP_Post $post): array
    {
        $o = new self($post);
        return $o->toArray();
    }
}
