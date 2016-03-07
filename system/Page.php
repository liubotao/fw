<?php


class Page {

    protected $_config = array(
            'prevNum' => 3,
            'nextNum' => 3,
            'showSinglePage' => false,
            'prefix' => '<div class="page-box mt30"><div class="page-wrap clearfix"><ul class="index-page fl">',
            'first' => '<li><a class="ico-page-first hide-text" href="%link%" title="%page%">%page%</a></li>',
            'last' => '<li><a class="ico-page-last hide-text" href="%link%" title="最后一页">%page%</a></li>',
            'prev' => '<li><a class="ico-page-prev hide-text" href="%link%" title="上一页">上一页</a></li>',
            'next' => '<li><a class="ico-page-next hide-text" href="%link%" title="下一页">下一页</a></li>',
            'current' => '<li><a class="ico-page on-choose" href="javascript:" title="第%page%页">%page%</a></li>',
            'page' => '<li> <a class="ico-page" href="%link%" title="第%page%页">%page%</a> </li>',
            'suffix' => '</div></div>'
    );

    public $curPage = 1;

    public $pageSize = 20;

    public $totalItems;

    public $url;

    public $totalPages;

    public $startPage;

    public $endPage;


    public function __construct($curPage = 1, $pageSize = 20, $totalItems, $url = '') {
        $this->curPage = intval($curPage);
        $this->pageSize = intval($pageSize);
        $this->totalItems = intval($totalItems);
        $this->url = $url;

        $this->_init();
    }


    public function config($key = null, $value = null) {
        if (is_null($key)) return $this->_config;

        if (is_array($key)) {
            $this->_config = $key + $this->_config;
            $this->_init();
            return $this;
        }

        if (is_null($value)) return $this->_config[$key];

        $this->_config[$key] = $value;
        $this->_init();
        return $this;
    }


    public function prefix($format = null) {
        if (is_null($format)) $format = $this->_config['prefix'];

        return $this->page(null, $format);
    }


    public function suffix($format = null) {
        if (is_null($format)) $format = $this->_config['suffix'];

        return $this->page(null, $format);
    }


    public function first($format = null) {
        if (is_null($format)) $format = $this->_config['first'];

        return $this->page(1, $format);
    }


    public function last($format = null) {
        if (is_null($format)) $format = $this->_config['last'];
        return $this->page($this->totalPages, $format);
    }


    public function prev($format = null) {
        if (1 == $this->curPage) return '';

        if (is_null($format)) $format = $this->_config['prev'];

        $page = $this->curPage - 1;

        return $this->page($page, $format);
    }


    public function next($format = null) {
        if ($this->curPage == $this->totalPages) return '';

        if (is_null($format)) $format = $this->_config['next'];

        $page = $this->curPage + 1;

        return $this->page($page, $format);
    }


    public function current($format = null) {
        if (is_null($format)) $format = $this->_config['current'];

        return $this->page($this->curPage, $format);

    }


    public function page($page, $format = null) {
        if (is_null($format)) $format = $this->_config['page'];

        $p = array(
                '%link%' => $this->url,
                '%curPage%' => $this->curPage,
                '%pageSize%' => $this->pageSize,
                '%totalItems%' => $this->totalItems,
                '%totalPages%' => $this->totalPages,
                '%startPage%' => $this->startPage,
                '%endPage%' => $this->endPage,
                '%page%' => $page
        );

        return str_replace(array_keys($p), $p, $format);
    }


    public function html() {
        if (1 >= $this->totalPages && !$this->_config['showSinglePage']) return '';

        $html = $this->prefix();

        if (1 == $this->startPage - 1) {
            $html .= $this->first();
        } elseif (1 < $this->startPage - 1) {
            $html .= $this->first();

        }

        $html .= $this->prev();
        for ($i = $this->startPage; $i <= $this->endPage; $i++) {
            $html .= ($i == $this->curPage ? $this->current() : $this->page($i));
        }

        if (1 == $this->totalPages - $this->endPage) {
            $html .= $this->page($this->totalPages);
        } elseif (1 < $this->totalPages - $this->endPage) {
            $html .= $this->last();
        }
        $html .= $this->next();
        $html .= $this->suffix();

        return $html;
    }


    public function display() {
        echo $this->html();
    }


    public function _init() {
        if (1 > $this->pageSize) $this->pageSize = 20;
        $this->totalPages = ceil($this->totalItems / $this->pageSize);
        if (1 > $this->curPage || $this->curPage > $this->totalPages) $this->curPage = 1;

        $this->startPage = $this->curPage - $this->_config['prevNum'];
        if (1 > $this->startPage) $this->startPage = 1;

        $this->endPage = $this->curPage + $this->_config['nextNum'];

        $less = ($this->_config['prevNum'] + $this->_config['nextNum']) - ($this->endPage - $this->startPage);
        if (0 < $less) $this->endPage += $less;
        if ($this->endPage > $this->totalPages) $this->endPage = $this->totalPages;

        $less = ($this->_config['prevNum'] + $this->_config['nextNum']) - ($this->endPage - $this->startPage);
        if (0 < $less) $this->startPage -= $less;
        if (1 > $this->startPage) $this->startPage = 1;
    }
}