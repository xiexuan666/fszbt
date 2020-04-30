{extend name="public/container"}
{block name="content"}
<div class="layui-fluid" style="background: #fff;margin-top: -10px;">
    <div class="layui-tab layui-tab-brief" lay-filter="tab">
        <ul class="layui-tab-title">
            <li lay-id="list" {eq name='type' value='1'}class="layui-this" {/eq} >
                <a href="{eq name='type' value='1'}javascript:;{else}{:Url('index',['type'=>1])}{/eq}">展示中品牌({$onsale})</a>
            </li>
            <li lay-id="list" {eq name='type' value='2'}class="layui-this" {/eq}>
                <a href="{eq name='type' value='2'}javascript:;{else}{:Url('index',['type'=>2])}{/eq}">待上架品牌({$forsale})</a>
            </li>
            <li lay-id="list" {eq name='type' value='3'}class="layui-this" {/eq}>
                <a href="{eq name='type' value='3'}javascript:;{else}{:Url('index',['type'=>3])}{/eq}">仓库中品牌({$warehouse})</a>
            </li>
            <li lay-id="list" {eq name='type' value='6'}class="layui-this" {/eq}>
                <a href="{eq name='type' value='6'}javascript:;{else}{:Url('index',['type'=>6])}{/eq}">品牌回收站({$recycle})</a>
            </li>
        </ul>
    </div>
    <div class="layui-row layui-col-space15"  id="app">
        <div class="layui-col-md12">
            <div class="layui-card">
                <div class="layui-card-body">
                    <form class="layui-form layui-form-pane" action="">
                        <div class="layui-form-item">
                            <div class="layui-inline">
                                <label class="layui-form-label">所有分类</label>
                                <div class="layui-input-block">
                                    <select name="cate_id">
                                        <option value=" ">全部</option>
                                        {volist name='cate' id='vo'}
                                        <option value="{$vo.id}">{$vo.html}{$vo.cate_name}</option>
                                        {/volist}
                                    </select>
                                </div>
                            </div>
                            <div class="layui-inline">
                                <label class="layui-form-label">品牌名称</label>
                                <div class="layui-input-block">
                                    <input type="text" name="name" class="layui-input" placeholder="请输入品牌,关键字,编号">
                                    <input type="hidden" name="type" value="{$type}">
                                </div>
                            </div>
                            <div class="layui-inline">
                                <div class="layui-input-inline">
                                    <button class="layui-btn layui-btn-sm layui-btn-normal" lay-submit="search" lay-filter="search">
                                        <i class="layui-icon layui-icon-search"></i>搜索</button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!--产品列表-->
        <div class="layui-col-md12">
            <div class="layui-card">
                <div class="layui-card-body">
                    <div class="alert alert-info" role="alert">
                        列表[品牌排序]可进行快速修改,双击或者单击进入编辑模式,失去焦点可进行自动保存
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    </div>
                    <div class="layui-btn-container">
                        {switch name='type'}
                            {case value="1"}
                                <button class="layui-btn layui-btn-sm" onclick="$eb.createModalFrame(this.innerText,'{:Url('create')}',{h:700,w:1100})">添加品牌</button>
                            {/case}
                            {case value="2"}
                                <button class="layui-btn layui-btn-sm" data-type="show">批量上架</button>
                            {/case}
                        {/switch}
                    </div>
                    <table class="layui-hide" id="List" lay-filter="List"></table>
                    <!--图片-->
                    <script type="text/html" id="image">
                        <img style="cursor: pointer" lay-event="open_image" src="{{d.logo}}">
                    </script>
                    <!--上架|下架-->
                    <script type="text/html" id="checkboxstatus">
                        <input type='checkbox' name='id' lay-skin='switch' value="{{d.id}}" lay-filter='is_show' lay-text='上架|下架'  {{ d.is_show == 1 ? 'checked' : '' }}>
                    </script>
                    <!--产品名称-->
                    <script type="text/html" id="store_name">
                        <h4>
                            {{d.cate_name}}
                        </h4>
                    </script>
                    <!--操作-->
                    <script type="text/html" id="act">
                        <button type="button" class="layui-btn layui-btn-xs layui-btn-normal" onclick="$eb.createModalFrame('{{d.name}}-编辑','{:Url('edit')}?id={{d.id}}',{h:700,w:1100})">
                            编辑
                        </button>
                        <button type="button" class="layui-btn layui-btn-xs" onclick="dropdown(this)">操作 <span class="caret"></span></button>
                        <ul class="layui-nav-child layui-anim layui-anim-upbit">
                            {{# if(d.is_del){ }}
                            <li>
                                <a href="javascript:void(0);" lay-event='delstor'>
                                    <i class="fa fa-trash"></i> 恢复品牌
                                </a>
                            </li>
                            {{# }else{ }}
                            <li>
                                <a href="javascript:void(0);" lay-event='delstor'>
                                    <i class="fa fa-trash"></i> 移到回收站
                                </a>
                            </li>
                            {{# } }}
                        </ul>
                    </script>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="{__ADMIN_PATH}js/layuiList.js"></script>
<script>
    var type=<?=$type?>;
    //实例化form
    layList.form.render();
    //加载列表
    layList.tableList('List',"{:Url('product_ist',['type'=>$type])}",function (){
        var join=new Array();
        switch (parseInt(type)){
            case 1:case 3:
                join=[
                    {field: 'id', title: 'ID', sort: true,event:'id',width:'6%'},
                    {field: 'image', title: '品牌logo',templet:'#image',width:'10%'},
                    {field: 'store_name', title: '品牌分类',templet:'#store_name'},
                    {field: 'name', title: '品牌名称',templet:'#name'},
                    {field: 'add_time', title: '添加时间',align:'center',event:'add_time',width:'14%'},
                    {field: 'sort', title: '排序',edit:'sort',align:'center',width:'6%'},
                    {field: 'status', title: '状态',templet:"#checkboxstatus",align:'center',width:'8%'},
                    {field: 'right', title: '操作',align:'center',toolbar:'#act',width:'14%'},
                ];
                break;
            case 2:
                join=[
                    {type:'checkbox'},
                    {field: 'id', title: 'ID', sort: true,event:'id',width:'6%'},
                    {field: 'image', title: '品牌logo',templet:'#image',width:'10%'},
                    {field: 'name', title: '品牌名称',templet:'#name'},
                    {field: 'sort', title: '排序',edit:'sort',width:'6%'},
                    {field: 'status', title: '状态',templet:"#checkboxstatus",width:'8%'},
                    {field: 'right', title: '操作',align:'center',toolbar:'#act',width:'14%'},
                ];
                break;
            case 6:
                join=[
                    {field: 'id', title: '产品ID', sort: true,event:'id'},
                    {field: 'image', title: '品牌logo',templet:'#image'},
                    {field: 'name', title: '品牌名称',templet:'#sname'},
                    {field: 'sort', title: '排序',edit:'sort'},
                    {field: 'right', title: '操作',align:'center',toolbar:'#act',width:'14%'},
                ];
                break;
        }
        return join;
    })
    //excel下载
    layList.search('export',function(where){
        location.href=layList.U({c:'store.brand_product',a:'product_ist',q:{
                cate_id:where.cate_id,
                store_name:where.store_name,
                type:where.type,
                excel:1
            }});
    })
    //下拉框
    $(document).click(function (e) {
        $('.layui-nav-child').hide();
    })
    function dropdown(that){
        var oEvent = arguments.callee.caller.arguments[0] || event;
        oEvent.stopPropagation();
        var offset = $(that).offset();
        var top=offset.top-$(window).scrollTop();
        var index = $(that).parents('tr').data('index');
        $('.layui-nav-child').each(function (key) {
            if (key != index) {
                $(this).hide();
            }
        })
        if($(document).height() < top+$(that).next('ul').height()){
            $(that).next('ul').css({
                'padding': 10,
                'top': - ($(that).parent('td').height() / 2 + $(that).height() + $(that).next('ul').height()/2),
                'min-width': 'inherit',
                'position': 'absolute'
            }).toggle();
        }else{
            $(that).next('ul').css({
                'padding': 10,
                'top':$(that).parent('td').height() / 2 + $(that).height(),
                'min-width': 'inherit',
                'position': 'absolute'
            }).toggle();
        }
    }
    //快速编辑
    layList.edit(function (obj) {
        var id=obj.data.id,value=obj.value;
        switch (obj.field) {
            case 'price':
                action.set_product('price',id,value);
                break;
            case 'stock':
                action.set_product('stock',id,value);
                break;
            case 'sort':
                action.set_product('sort',id,value);
                break;
            case 'ficti':
                action.set_product('ficti',id,value);
                break;
        }
    });
    //上下加产品
    layList.switch('is_show',function (odj,value) {
        if(odj.elem.checked==true){
            layList.baseGet(layList.Url({c:'store.brand_product',a:'set_show',p:{is_show:1,id:value}}),function (res) {
                layList.msg(res.msg);
            });
        }else{
            layList.baseGet(layList.Url({c:'store.brand_product',a:'set_show',p:{is_show:0,id:value}}),function (res) {
                layList.msg(res.msg);
            });
        }
    });
    //点击事件绑定
    layList.tool(function (event,data,obj) {
        switch (event) {
            case 'delstor':
                var url=layList.U({c:'store.brand_product',a:'delete',q:{id:data.id}});
                if(data.is_del) var code = {title:"操作提示",text:"确定恢复新品操作吗？",type:'info',confirm:'是的，恢复该新品'};
                else var code = {title:"操作提示",text:"确定将该新品移入回收站吗？",type:'info',confirm:'是的，移入回收站'};
                $eb.$swal('delete',function(){
                    $eb.axios.get(url).then(function(res){
                        if(res.status == 200 && res.data.code == 200) {
                            $eb.$swal('success',res.data.msg);
                            obj.del();
                        }else
                            return Promise.reject(res.data.msg || '删除失败')
                    }).catch(function(err){
                        $eb.$swal('error',err);
                    });
                },code)
                break;
            case 'open_image':
                $eb.openImage(data.image);
                break;
        }
    })
    //排序
    layList.sort(function (obj) {
        var type = obj.type;
        switch (obj.field){
            case 'id':
                layList.reload({order: layList.order(type,'p.id')},true,null,obj);
                break;
            case 'sales':
                layList.reload({order: layList.order(type,'p.sales')},true,null,obj);
                break;
        }
    });
    //查询
    layList.search('search',function(where){
        layList.reload(where);
    });
    //自定义方法
    var action={
        set_product:function(field,id,value){
            layList.baseGet(layList.Url({c:'store.brand_product',a:'set_product',q:{field:field,id:id,value:value}}),function (res) {
                layList.msg(res.msg);
            });
        },
        show:function(){
            var ids=layList.getCheckData().getIds('id');
            if(ids.length){
                layList.basePost(layList.Url({c:'store.brand_product',a:'product_show'}),{ids:ids},function (res) {
                    layList.msg(res.msg);
                    layList.reload();
                });
            }else{
                layList.msg('请选择要上架的新品');
            }
        }
    };
    //多选事件绑定
    $('.layui-btn-container').find('button').each(function () {
        var type=$(this).data('type');
        $(this).on('click',function(){
            action[type] && action[type]();
        })
    });
</script>
{/block}
