<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Contest extends Controller_Base
{

    public function action_index()
    {
        $this->view = 'contest/list';
        $this->action_list();
    }

    public function action_list()
    {
        // initial
        $page = $this->get_query('page', 1);

        $filter = array(
            'defunct' => Model_Base::DEFUNCT_NO,
        );
        $order_by = array(
            'contest_id' => Model_Base::ORDER_DESC
        );
        $contest_list = Model_Contest::find($filter, $page, OJ::per_page, $order_by);
        $total = Model_Contest::count($filter);

        // view
        $this->template_data['total'] = ceil($total / OJ::per_page);
        $this->template_data['list']  = $contest_list;
        $this->template_data['title'] = "Contest List";
    }

    public function action_talk()
    {
        $this->view = 'discuss/list';
        $page = $this->get_query('page', 1);
        $cid = $this->get_query('cid');

        $contest = Model_Contest::find_by_id($cid);

        $this->check_permission($contest);

        $filter = array(
            'cid' => $cid,
        );

        $filter = Model_Base::clean_data($filter);
        $topic_list = Model_Topic::page($filter, $page, OJ::per_page);
        $total = Model_Topic::count($filter);

        $this->template_data['contest'] = $contest;
        $this->template_data['cid'] = $cid;
        $this->template_data['topic_list'] = $topic_list;
        $this->template_data['total'] = ceil( $total / OJ::per_page);
        $this->template_data['title'] = sprintf('%s - Discuss', $contest->title);
    }

    public function action_show()
    {
        // init
        $cid = $this->request->param('id', null);

        if (is_null($cid)) {
            $this->redirect(Route::url('default'));
        }

        $contest = Model_Contest::find_by_id($cid);
        $this->check_permission($contest);

        $this->template_data['cid'] = $cid;
        $this->template_data['contest'] = $contest;
        $this->template_data['title'] = "{$contest['title']}";
    }

    public function action_standing()
    {
        $cid = $this->request->param('id', null);
        if ($cid === null) {
            $this->redirect(Route::url('default'));
        }

        $contest = Model_Contest::find_by_id($cid);
        $this->check_permission($contest);

        $this->template_data['cid']     = $cid;
        $this->template_data['contest'] = $contest;
        $this->template_data['title']   = "{$contest['title']} - Standing";
    }

    public function action_statistics()
    {
        // init
        $cid = $this->request->param('id', null);
        if ($cid === null) {
            $this->redirect(Route::url('default'));
        }
        $contest = Model_Contest::find_by_id($cid);

        $this->check_permission($contest);

        $ret = $contest->statistics();
        $this->template_data['contest'] = $contest;
        $this->template_data['result'] = $ret['result'];
        $this->template_data['language'] = $ret['language'];
        $this->template_data['title'] = "{$contest['title']} - Statistics";
        $this->template_data['cid'] = $cid;
    }

    public function action_problem()
    {
        //TODO: check
        $this->view = 'problem/show';
        $pid = $this->request->param('pid', null);
        $cid = $this->request->param('cid', null);

        if (is_null($pid) or is_null($cid)) {
            // wrong url
            $this->redirect(Route::url('default'));
        }

        $contest = Model_Contest::find_by_id($cid);
        $this->check_permission($contest);

        if ($contest == null) {
            $error = 'No Such Contest';
            $this->error_page($error);
        } else {
            if ( $contest->is_open() ) {
                $problem = $contest->problem(intval($pid));
            } else {
                $error = 'Contest is not Open';
                $this->error_page($error);
            }
        }
        $this->template_data['contest'] = $contest;
        $this->template_data['title']   = "{$contest['title']}";
        $this->template_data['cid']     = $cid;
        $this->template_data['problem'] = $problem;
        $this->template_data['pid']     = $pid;
    }

    /**
     * @param Model_Contest $contest
     */
    protected function check_permission($contest)
    {
        $current_user = Auth::instance()->get_user();
        if ( $contest AND $contest->can_user_access($current_user))
        {
            return ;
        }
        //TODO: add more notice
        if ( $current_user )
            $message = '您没有权限访问该私有比赛';
        else {
            $message = '请先登录后再查看此比赛';
        }
        $this->error_page($message);
    }
}