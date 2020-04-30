{extend name="public/container"}
{block name="content"}
<div class="layui-fluid" style="background: #fff;margin-top: -10px;">
    <div class="layui-tab layui-tab-brief" lay-filter="tab">
        <ul class="layui-tab-title">
            <li lay-id="list" {eq name='type' value='1'}class="layui-this" {/eq} >
                <a href="{eq name='type' value='1'}javascript:;{else}{:Url('index',['type'=>1])}{/eq}">进行中的课程({$onsale})</a>
            </li>
            <li lay-id="list" {eq name='type' value='2'}class="layui-this" {/eq}>
                <a href="{eq name='type' value='2'}javascript:;{else}{:Url('index',['type'=>2])}{/eq}">隐藏课程({$forsale})</a>
            </li>
            <li lay-id="list" {eq name='type' value='3'}class="layui-this" {/eq}>
                <a href="{eq name='type' value='3'}javascript:;{else}{:Url('index',['type'=>3])}{/eq}">显示课程({$warehouse})</a>
            </li>
            <li lay-id="list" {eq name='type' value='4'}class="layui-this" {/eq}>
                <a href="{eq name='type' value='4'}javascript:;{else}{:Url('index',['type'=>4])}{/eq}">已经售馨课程({$outofstock})</a>
            </li>
            <li lay-id="list" {eq name='type' value='5'}class="layui-this" {/eq}>
                <a href="{eq name='type' value='5'}javascript:;{else}{:Url('index',['type'=>5])}{/eq}">警戒库存({$policeforce})</a>
            </li>
            <li lay-id="list" {eq name='type' value='6'}class="layui-this" {/eq}>
                <a href="{eq name='type' value='6'}javascript:;{else}{:Url('index',['type'=>6])}{/eq}">课程回收站({$recycle})</a>
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
                                <label class="layui-form-label">课程名称</label>
                                <div class="layui-input-block">
                                    <input type="text" name="store_name" class="layui-input" placeholder="请输入课程名称,关键字,编号">
                                    <input type="hidden" name="type" value="{$type}">
                                </div>
                            </div>
                            <div class="layui-inline">
                                <div class="layui-input-inline">
                                    <button class="layui-btn layui-btn-sm layui-btn-normal" lay-submit="search" lay-filter="search">
                                        <i class="layui-icon layui-icon-search"></i>搜索</button>
                                    <button class="layui-btn layui-btn-primary layui-btn-sm export"  lay-submit="export" lay-filter="export">
                                        <i class="fa fa-floppy-o" style="margin-right: 3px;"></i>导出</button>
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
                        列表[课程价格],[虚拟销量],[库存人数]可进行快速修改,双击或者单击进入编辑模式,失去焦点可进行自动保存
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    </div>
                    <div class="layui-btn-container">
                        {switch name='type'}
                            {case value="1"}
                                <button class="layui-btn layui-btn-sm" onclick="$eb.createModalFrame(this.innerText,'{:Url('create')}',{h:800,w:1000})">添加课程</button>
                            {/case}
                            {case value="2"}
                                <button class="layui-btn layui-btn-sm" data-type="show">批量显示</button>
                            {/case}
                        {/switch}
                    </div>
                    <table class="layui-hide" id="List" lay-filter="List"></table>
                    <!--图片-->
                    <script type="text/html" id="image">
                        <img style="cursor: pointer" lay-event="open_image" src="{{d.image}}">
                    </script>
                    <!--上架|下架-->
                    <script type="text/html" id="checkboxstatus">
                        <input type='checkbox' name='id' lay-skin='switch' value="{{d.id}}" lay-filter='is_show' lay-text='显示|隐藏'  {{ d.is_show == 1 ? 'checked' : '' }}>
                    </script>
                    <!--是否置顶-->
                    <script type="text/html" id="ishot">
                        <input type='checkbox' name='id' lay-skin='switch' value="{{d.id}}" lay-filter='is_hot' lay-text='是|否'  {{ d.is_hot == 1 ? 'checked' : '' }}>
                    </script>
                    <!--收藏-->
                    <script type="text/html" id="like">
                        <span><i class="layui-icon layui-icon-praise"></i> {{d.like}}</span>
                    </script>
                    <!--点赞-->
                    <script type="text/html" id="collect">
                        <span><i class="layui-icon layui-icon-star"></i> {{d.collect}}</span>
                    </script>
                    <!--产品名称-->
                    <script type="text/html" id="store_name">
                        <h4>{{d.store_name}}</h4>
                        <p>原价:<font color="red">{{d.price}}</font> </p>
                        <p>优惠最低价:<font color="red">{{d.vip_price}}</font> </p>
                    </script>
                    <!--操作-->
                    <script type="text/html" id="act">
                        <button type="button" class="layui-btn layui-btn-xs layui-btn-normal" onclick="$eb.createModalFrame('{{d.store_name}}-编辑','{:Url('edit')}?id={{d.id}}',{h:800,w:1000})">
                            编辑
                        </button>
                        <button type="button" class="layui-btn layui-btn-xs btn-success" onclick="$eb.createModalFrame('{{d.store_name}}-套餐','{:Url('attr')}?id={{d.id}}',{h:800,w:1000})">
                            设置套餐
                        </button>
                        <button type="button" class="layui-btn layui-btn-xs" onclick="dropdown(this)">操作 <span class="caret"></span></button>
                        <ul class="layui-nav-child layui-anim layui-anim-upbit">
                            <li>
                                <a href="javascript:void(0);" class="" onclick="$eb.createModalFrame(this.innerText,'{:Url('edit_content')}?id={{d.id}}',{h:800,w:1000})">
                                    <i class="fa fa-pencil"></i> 课程详情</a>
                            </li>
                            {{# if(d.is_del){ }}
                            <li>
                                <a href="javascript:void(0);" lay-event='delstor'>
                                    <i class="fa fa-trash"></i> 恢复课程
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
    layList.tableList('List',"{:Url('course_ist',['type'=>$type])}",function (){
        var join=new Array();
        switch (parseInt(type)){
            case 1:case 3:case 4:case 5:
                join=[
                    {field: 'id', title: 'ID', sort: true,event:'id',width:'6%'},
                    {field: 'image', title: '课程主图',templet:'#image',width:'10%'},
                    {field: 'store_name', title: '课程名称',templet:'#store_name'},
                    {field: 'sort', title: '课程排序',edit:'sort',width:'6%'},
                    {field: 'stock', title: '课程人数',edit:'stock',width:'8%'},
                    {field: 'ficti', title: '虚拟销量',edit:'ficti',width:'8%'},
                    {field: 'sales', title: '课程销量',sort: true,event:'sales',width:'8%'},
                    {field: 'status', title: '课程状态',templet:"#checkboxstatus",width:'8%'},
                    {field: 'is_hot', title: '是否置顶',templet:"#ishot",width:'8%'},
                    {field: 'right', title: '操作',align:'center',toolbar:'#act',width:'14%'},
                ];
                break;
            case 2:
                join=[
                    {type:'checkbox'},
                    {field: 'id', title: 'ID', sort: true,event:'id',width:'6%'},
                    {field: 'image', title: '课程主图',templet:'#image',width:'10%'},
                    {field: 'store_name', title: '课程名称',templet:'#store_name'},
                    {field: 'price', title: '课程价格',edit:'price',width:'8%'},
                    {field: 'sort', title: '课程排序',edit:'sort',width:'6%'},
                    {field: 'stock', title: '课程人数',edit:'stock',width:'6%'},
                    {field: 'ficti', title: '虚拟销量',edit:'ficti',width:'8%'},
                    {field: 'sales', title: '课程销量',sort: true,event:'sales',width:'6%'},
                    {field: 'status', title: '课程状态',templet:"#checkboxstatus",width:'8%'},
                    {field: 'is_hot', title: '是否置顶',templet:"#ishot",width:'8%'},
                    {field: 'right', title: '操作',align:'center',toolbar:'#act',width:'14%'},
                ];
                break;
            case 6:
                join=[
                    {field: 'id', title: 'ID', sort: true,event:'id'},
                    {field: 'image', title: '课程主图',templet:'#image'},
                    {field: 'store_name', title: '课程名称',templet:'#store_name'},
                    {field: 'price', title: '课程价格',edit:'price'},
                    {field: 'ficti', title: '虚拟销量',edit:'ficti'},
                    {field: 'stock', title: '课程人数',edit:'stock'},
                    {field: 'sort', title: '课程排序',edit:'sort'},
                    {field: 'sales', title: '课程销量',sort: true,event:'sales'},
                    {field: 'right', title: '操作',align:'center',toolbar:'#act',width:'14%'},
                ];
                break;
        }
        return join;
    })
    //excel下载
    layList.search('export',function(where){
        location.href=layList.U({c:'store.store_course',a:'course_ist',q:{
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
                action.set_course('price',id,value);
                break;
            case 'stock':
                action.set_course('stock',id,value);
                break;
            case 'sort':
                action.set_course('sort',id,value);
                break;
            case 'ficti':
                action.set_course('ficti',id,value);
                break;
        }
    });
    //上下加产品
    layList.switch('is_show',function (odj,value) {
        if(odj.elem.checked==true){
            layList.baseGet(layList.Url({c:'store.store_course',a:'set_show',p:{is_show:1,id:value}}),function (res) {
                layList.msg(res.msg);
            });
        }else{
            layList.baseGet(layList.Url({c:'store.store_course',a:'set_show',p:{is_show:0,id:value}}),function (res) {
                layList.msg(res.msg);
            });
        }
    });
    //上下加产品
    layList.switch('is_hot',function (odj,value) {
        if(odj.elem.checked==true){
            layList.baseGet(layList.Url({c:'store.store_course',a:'set_hot',p:{is_hot:1,id:value}}),function (res) {
                layList.msg(res.msg);
            });
        }else{
            layList.baseGet(layList.Url({c:'store.store_course',a:'set_hot',p:{is_hot:0,id:value}}),function (res) {
                layList.msg(res.msg);
            });
        }
    });
    //点击事件绑定
    layList.tool(function (event,data,obj) {
        switch (event) {
            case 'delstor':
                var url=layList.U({c:'store.store_course',a:'delete',q:{id:data.id}});
                if(data.is_del) var code = {title:"操作提示",text:"确定恢复产品操作吗？",type:'info',confirm:'是的，恢复该课程'};
                else var code = {title:"操作提示",text:"确定将该产品移入回收站吗？",type:'info',confirm:'是的，移入回收站'};
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
        set_course:function(field,id,value){
            layList.baseGet(layList.Url({c:'store.store_course',a:'set_course',q:{field:field,id:id,value:value}}),function (res) {
                layList.msg(res.msg);
            });
        },
        show:function(){
            var ids=layList.getCheckData().getIds('id');
            if(ids.length){
                layList.basePost(layList.Url({c:'store.store_course',a:'course_show'}),{ids:ids},function (res) {
                    layList.msg(res.msg);
                    layList.reload();
                });
            }else{
                layList.msg('请选择要显示的课程');
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