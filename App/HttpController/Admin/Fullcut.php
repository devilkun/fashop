<?php
/**
 *
 * Copyright  FaShop
 * License    http://www.fashop.cn
 * link       http://www.fashop.cn
 * Created by FaShop.
 * User: hanwenbo
 * Date: 2018/1/4
 * Time: 下午5:51
 *
 */

namespace App\HttpController\Admin;

use App\Utils\Code;
use ezswoole\Validator;

/**
 * 满减优惠
 * Class Shop
 * @package App\HttpController\Admin
 */
class Fullcut extends Admin
{

	/**
	 * 满减优惠活动列表
	 * @method GET
	 * @param string keywords   关键词 活动名称
	 * @param int    state        活动状态 0未开始 1进行中 2已结束
	 */
	public function list()
	{
		$condition = [];
		if( $this->get['keywords'] ){
			$condition['title'] = ['like', '%'.$this->get['keywords'].'%'];
		}

		if( $this->get['state'] ){
			switch( $this->get['state'] ){
			case 0:
				$condition['start_time'] = ['>', time()];
			break;

			case 1:
				$condition['start_time'] = ['<=', time()];
				$condition['end_time']   = ['>', time()];
			break;

			case 2:
				$condition['end_time'] = ['<=', time()];
			break;

			}
		}

		$count = \App\Model\Fullcut::getFullcutCount( $condition );
		$list  = \App\Model\Fullcut::getFullcutList( $condition, '*', 'id desc', $this->getPageLimit() );
		return $this->send( Code::success, [
			'total_number' => $count,
			'list'         => $list,
		] );
	}

	/**
	 * 满减优惠活动详情
	 * @method GET
	 * @param int    id        活动id
	 */
	public function info()
	{
		$get   = $this->get;
		$error = $this->validator( $get, 'Admin/Fullcut.info' );
		if( $error !== true ){
			return $this->send( Code::error, [], $error );
		} else{

			$condition       = [];
			$condition['id'] = $get['id'];
			$result          = [];
			$result['info']  = \App\Model\Fullcut::getFullcutInfo( $condition, '*' );
			return $this->send( Code::success, $result );
		}
	}

	/**
	 * 添加满减活动
	 * @method POST
	 * @param string    title        活动名称
	 * @param string    start_time  活动开始时间 yyyy-mm-dd hh:ii：ss
	 * @param string    end_time    活动结束时间 yyyy-mm-dd hh:ii：ss
	 * @param array     hierarchy    层级 至多5个,每个(包涵fll_price满XXX元,minus减XXX元,discountsXXX折,type满减类型 默认0减XXX元 1打XXX折)
	 * @param int       level        级别 默认0全店 1商品级
	 */
	public function add()
	{

		//参数格式
		// $post['hierarchy'] = array(
		// 	array(
		// 		'fll_price'	=>100,
		// 		'minus'		=>20,
		// 		'discounts'	=>0,
		// 		'type'		=>0,
		// 	),

		// 	array(
		// 		'fll_price'	=>100,
		// 		'minus'		=>0,
		// 		'discounts'	=>9.5,
		// 		'type'		=>1,
		// 	),

		// );

		$post  = $this->post;
		$error = $this->validator( $post, 'Admin/Fullcut.add' );
		if( $error !== true ){
			return $this->send( Code::error, [], $error );
		} else{

			$post['start_time'] = strtotime( $post['start_time'] );
			$post['end_time']   = strtotime( $post['end_time'] );

			if( intval( $post['start_time'] ) >= ($post['end_time']) ){
				return $this->send( Code::error, [], '开始时间必须小于结束时间' );

			}

			$post['create_time'] = time();

			$result = \App\Model\Fullcut::insertFullcut( $post );
			if( $result ){
				return $this->send( Code::success );
			} else{
				return $this->send( Code::error );
			}
		}
	}

	/**
	 * 编辑满减活动
	 * @method POST
	 * @param int    id            活动id
	 * @param string title        活动名称
	 * @param string start_time    活动开始时间 yyyy-mm-dd hh:ii：ss
	 * @param string end_time        活动结束时间 yyyy-mm-dd hh:ii：ss
	 * @param array  hierarchy        层级 至多5个,每个(包涵fll_price满XXX元,minus减XXX元,discountsXXX折,type满减类型 默认0减XXX元 1打XXX折)
	 * @param int    level            级别 默认0全店 1商品级
	 */
	public function edit()
	{
		$post  = $this->post;
		$error = $this->validator( $post, 'Admin/Fullcut.edit' );
		if( $error !== true ){
			return $this->send( Code::error, [], $error );
		} else{

			$condition          = [];
			$condition['id']    = $post['id'];
			$post['start_time'] = strtotime( $post['start_time'] );
			$post['end_time']   = strtotime( $post['end_time'] );

			if( intval( $post['start_time'] ) >= ($post['end_time']) ){
				return $this->send( Code::error, [], '开始时间必须小于结束时间' );

			}

			unset( $post['id'] );

			$result = \App\Model\Fullcut::updateFullcut( $condition, $post );

			if( $result ){
				return $this->send( Code::success, [], '修改成功' );
			} else{
				return $this->send( Code::error );
			}

		}
	}

	/**
	 * 删除满减活动
	 * @method POST
	 * @param int id 活动id
	 */
	public function del()
	{
		$post  = $this->post;
		$error = $this->validator( $post, 'Admin/Fullcut.del' );
		if( $error !== true ){
			return $this->send( Code::error, [], $error );
		} else{
			$condition       = [];
			$condition['id'] = $post['id'];

			\App\Model\Fullcut::startTransaction();// 启动事务

			//查询活动
			$row = \App\Model\Fullcut::getFullcutInfo( $condition, '*' );
			if( !$row ){
				\App\Model\Fullcut::rollback();// 回滚事务
				return $this->send( Code::param_error );
			}

			//删除活动
			$fullcut_result = \App\Model\Fullcut::delFullcut( $condition );

			if( !$fullcut_result ){
				\App\Model\Fullcut::rollback();// 回滚事务
				return $this->send( Code::error );
			}


			//级别 默认0全店 1商品级
			if( $row['level'] == 1 ){
				//删除活动下商品
				$fullcut_goods_result = \App\Model\FullcutGoods::delFullcutGoods( ['fullcut_id' => $post['id']] );

				if( !$fullcut_goods_result ){
					\App\Model\Fullcut::rollback();// 回滚事务
					return $this->send( Code::error );
				}
			}


			\App\Model\Fullcut::commit();// 提交事务

			return $this->send( Code::success );
		}
	}


	/**
	 * 满减优惠活动可选择商品列表
	 * @method GET
	 * @param int     fullcut_id  满减优惠活动id
	 * @param string keywords     关键词 商品名称
	 * @param array  category_ids商品分类
	 */
	public function selectableGoods()
	{

		$get   = $this->get;
		$error = $this->validator( $get, 'Admin/Fullcut.selectableGoods' );
		if( $error !== true ){
			return $this->send( Code::error, [], $error );
		} else{


			//查询活动
			$fullcut_data = \App\Model\Fullcut::getFullcutInfo( ['id' => $get['fullcut_id']], '*' );
			if( !$fullcut_data ){
				return $this->send( Code::param_error );
			}

			//级别 默认0全店 1商品级
			if( $fullcut_data['level'] == 0 ){
				return $this->send( Code::success, [
					'total_number' => 0,
					'list'         => 0,
				] );
			}

			$param = [];
			if( isset( $get['keywords'] ) ){
				$param['title'] = $get['keywords'];
			}

			if( isset( $get['category_ids'] ) ){
				$param['category_ids'] = $get['category_ids'];
			}

			//查询活动商品ids
			$goods_ids = \App\Model\FullcutGoods::getFullcutGoodsColumn( ['fullcut_id' => $get['fullcut_id']], 'goods_id' );
			if( $goods_ids ){
				$param['not_in_ids'] = $goods_ids;
			}

			$goodsLogic = new \App\Logic\GoodsSearch( $param );
			return $this->send( Code::success, [
				'total_number' => $goodsLogic->count(),
				'list'         => $goodsLogic->list(),
			] );

		}

	}

	/**
	 * 满减优惠活动已选择商品列表
	 * @method GET
	 * @param int     fullcut_id 满减优惠活动id
	 */
	public function selectedGoods()
	{

		$get   = $this->get;
		$error = $this->validator( $get, 'Admin/Fullcut.selectedGoods' );
		if( $error !== true ){
			return $this->send( Code::error, [], $error );
		} else{

			//查询活动
			$fullcut_data = \App\Model\Fullcut::getFullcutInfo( ['id' => $get['fullcut_id']], '*' );
			if( !$fullcut_data ){
				return $this->send( Code::param_error );
			}

			//级别 默认0全店 1商品级
			if( $fullcut_data['level'] == 0 ){
				return $this->send( Code::success, [
					'total_number' => 0,
					'list'         => 0,
				] );
			}

			//查询活动商品ids
			$goods_ids = \App\Model\FullcutGoods::getFullcutGoodsColumn( ['fullcut_id' => $get['fullcut_id']], 'goods_id' );
			if( $goods_ids ){
				$online_goods_ids = \App\Model\Goods::getGoodsColumn( ['in' => $goods_ids, 'is_on_sale' => 1], 'id' );
			}

			//交集 fullcut_goods表和goods表的商品交集
			$intersection_goods_ids = array_values( array_intersect( $goods_ids, $online_goods_ids ) );

			//删除活动下失效商品
			$fullcut_goods_result = \App\Model\FullcutGoods::delFullcutGoods( ['fullcut_id' => $get['fullcut_id'], 'goods_id' => ['not in', $intersection_goods_ids]] );
			if( !$fullcut_goods_result ){
				return $this->send( Code::error );
			}

			$param        = [];
			$param['ids'] = $intersection_goods_ids;

			$goodsLogic = new \App\Logic\GoodsSearch( $param );
			return $this->send( Code::success, [
				'total_number' => $goodsLogic->count(),
				'list'         => $goodsLogic->list(),
			] );

		}

	}

	/**
	 * 满减优惠活动选择商品
	 * @method POST
	 * @param int     fullcut_id 满减优惠活动id
	 * @param array  goods_ids   商品id
	 */
	public function choiceGoods()
	{

		$post  = $this->post;
		$error = $this->validator( $post, 'Admin/Fullcut.choiceGoods' );
		if( $error !== true ){
			return $this->send( Code::error, [], $error );
		} else{

			//查询活动
			$fullcut_data = \App\Model\Fullcut::getFullcutInfo( ['id' => $post['fullcut_id']], '*' );
			if( !$fullcut_data ){
				return $this->send( Code::param_error );
			}

			//级别 默认0全店 1商品级
			if( $fullcut_data['level'] == 0 ){
				return $this->send( Code::param_error );
			}

			$goods_sku_data = \App\Model\GoodsSku::getGoodsSkuList( ['goods_id' => ['in', $post['goods_ids']]], 'id AS goods_sku_id,goods_id', 'goods_id asc,goods_sku_id asc', '' );
			if( !$goods_sku_data ){
				return $this->send( Code::param_error );

			}

			foreach( $goods_sku_data as $key => $value ){
				$goods_sku_data[$key]['fullcut_id']  = $post['fullcut_id'];
				$goods_sku_data[$key]['create_time'] = time();
			}

			$result = \App\Model\FullcutGoods::insertAllFullcutGoods( $goods_sku_data );

			if( !$result ){
				return $this->send( Code::error );
			}

			return $this->send( Code::success );

		}

	}

	/**
	 * 满减优惠活动已选择商品sku列表
	 * @method GET
	 * @param int    fullcut_id 满减优惠活动id
	 * @param int    goods_id   满减优惠活动商品id
	 */
	public function goodsSkuList()
	{

		$get   = $this->get;
		$error = $this->validator( $get, 'Admin/Fullcut.goodsSkuList' );
		if( $error !== true ){
			return $this->send( Code::error, [], $error );
		} else{
			//查询活动
			$fullcut_data = \App\Model\Fullcut::getFullcutInfo( ['id' => $get['fullcut_id']], '*' );
			if( !$fullcut_data ){
				return $this->send( Code::param_error );
			}

			//级别 默认0全店 1商品级
			if( $fullcut_data['level'] == 0 ){
				return $this->send( Code::param_error );
			}

			$condition                       = [];
			$condition['goods_sku.goods_id'] = $get['goods_id'];

			//查询该商品下所有sku和已设置满减的数据
			$goods_sku_count = \App\Model\FullcutGoods::getGoodsSkuMoreCount( $condition );
			$goods_sku_list  = \App\Model\FullcutGoods::getGoodsSkuMoreList( $condition, 'goods_sku.*,fullcut_goods.fullcut_id', 'goods_sku.id asc', '' );

			return $this->send( Code::success, [
				'total_number' => $goods_sku_count,
				'list'         => $goods_sku_list,
			] );

		}

	}

	/**
	 * 修改满减优惠活动已选择商品sku
	 * @method GET
	 * 传过来是一个二维数组 里面的每个子数组下面有如下数据
	 * @param int    fullcut_id    满减优惠活动id
	 * @param int    goods_id        满减优惠活动商品id
	 * @param array  goods_sku      满减优惠活动商品sku ids
	 */
	public function editGoodsSku()
	{
		//post数据格式
		// $post['goods_sku'] = array(
		// 	'fullcut_id'	=>100,
		// 	'goods_id'		=>100,
		// 	'goods_sku'		=>array(1,2,3,4,5)
		// );

		$post = $this->post;

		$error = $this->validator( $post, 'Admin/Fullcut.editGoodsSku' );
		if( $error !== true ){
			return $this->send( Code::error, [], $error );

		} else{

			\App\Model\FullcutGoods::startTransaction();// 启动事务
			//为空代表删除所有goods_sku
			if( empty( $post['goods_sku'] ) ){

				$condition               = [];
				$condition['fullcut_id'] = $post['fullcut_id'];
				$condition['goods_id']   = $post['goods_id'];

				//删除活动下商品
				$fullcut_goods_result = \App\Model\FullcutGoods::delFullcutGoods( $condition );
				if( !$fullcut_goods_result ){
					\App\Model\FullcutGoods::rollback();// 回滚事务
					return $this->send( Code::error );
				}

			} else{
				$post_goods_sku = [];
				foreach( $post['goods_sku'] as $key => $value ){
					$post_goods_sku[$key]['fullcut_id']   = $post['fullcut_id'];
					$post_goods_sku[$key]['goods_id']     = $post['goods_id'];
					$post_goods_sku[$key]['goods_sku_id'] = $value;
					$post_goods_sku[$key]['create_time']  = time();

				}

				//查询活动商品sku ids
				$goods_sku_ids = \App\Model\FullcutGoods::getFullcutGoodsColumn( $condition, 'goods_sku_id' );

				if( $goods_sku_ids ){

					$post_goods_sku_ids = array_column( $post_goods_sku, 'goods_sku_id' );

					//交集
					$intersection_goods_sku_ids = array_intersect( $goods_sku_ids, $post_goods_sku_ids );

					//返回出现在第一个数组中但其他数组中没有的值 [新添加的sku]
					$difference_goods_sku_add_ids = array_diff( $goods_sku_ids, $post_goods_sku_ids );

					//返回出现在第一个数组中但其他数组中没有的值 [已删除的sku]
					$difference_goods_sku_del_ids = array_diff( $post_goods_sku_ids, $goods_sku_ids );


					//交集
					if( $intersection_goods_sku_ids ){
						$fullcut_goods_updata = [];

						foreach( $post_goods_sku as $key => $value ){
							if( in_array( $value['goods_sku_id'], $intersection_goods_sku_ids ) ){
								$fullcut_goods_updata[] = $value;
							}
						}

						$result = \App\Model\FullcutGoods::editMultiFullcutGoods( $fullcut_goods_updata );
						if( !$result ){
							\App\Model\FullcutGoods::rollback();// 回滚事务
							return $this->send( Code::error );
						}

					}

					//差集 [新添加的sku]
					if( $difference_goods_sku_add_ids ){
						$fullcut_goods_insert_data = [];

						foreach( $post_goods_sku as $key => $value ){
							if( in_array( $value['goods_sku_id'], $difference_goods_sku_add_ids ) ){
								$fullcut_goods_insert_data[] = $value;
							}
						}

						$result = \App\Model\FullcutGoods::insertAllFullcutGoods( $fullcut_goods_insert_data );
						if( !$result ){
							\App\Model\FullcutGoods::rollback();// 回滚事务
							return $this->send( Code::error );
						}
					}

					//差集 [已删除的sku]
					if( $difference_goods_sku_del_ids ){
						$condition['goods_sku_id'] = ['in', $difference_goods_sku_del_ids];
						$result                    = \App\Model\FullcutGoods::delFullcutGoods( $condition );

						if( !$result ){
							\App\Model\FullcutGoods::rollback();// 回滚事务
							return $this->send( Code::error );
						}

					}

				} else{
					$result = \App\Model\FullcutGoods::insertAllFullcutGoods( $post_goods_sku );
					if( !$result ){
						\App\Model\FullcutGoods::rollback();// 回滚事务
						return $this->send( Code::error );
					}

				}


			}

			\App\Model\FullcutGoods::commit();// 提交事务
			return $this->send( Code::success );

		}

	}


}