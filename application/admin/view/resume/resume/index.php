{extend name="public/container"}
{block name="content"}
<div class="layui-fluid" style="background: #fff;margin-top: -10px;">
    <div class="layui-row layui-col-space15"  id="app">
        <div class="layui-col-md12">
            <div class="layui-card">
                <div class="layui-card-body">
                    <form class="layui-form layui-form-pane" action="">
                        <div class="layui-form-item">
                            <div class="layui-inline">
                                <label class="layui-form-label">关键词</label>
                                <div class="layui-input-block">
                                    <input type="text" name="keywords" class="layui-input" placeholder="请输入姓名,电话,编号">
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
                        列表[浏览量][排序]可进行快速修改,双击或者单击进入编辑模式,失去焦点可进行自动保存
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    </div>

                    <table class="layui-hide" id="List" lay-filter="List"></table>
                    <!--头像-->
                    <script type="text/html" id="image">
                        <img style="cursor: pointer" lay-event="open_image" src="{{d.avatar}}">
                    </script>
                    <!--上架|下架-->
                    <script type="text/html" id="checkboxstatus">
                        <input type='checkbox' name='id' lay-skin='switch' value="{{d.id}}" lay-filter='is_show' lay-text='显示|隐藏'  {{ d.is_show == 1 ? 'checked' : '' }}>
                    </script>
                    <!--点赞-->
                    <script type="text/html" id="collect">
                        <span><i class="layui-icon layui-icon-star"></i> {{d.collect}}</span>
                    </script>
                    <!--招聘信息-->
                    <script type="text/html" id="store_name">
                        <h4>职位：{{d.positionData}}</h4>
                        <p><font color="red">{{d.name}}·{{d.EducationName}}</font> </p>
                        {{# if(d.industryData!=''){ }}
                        <p>电话:{{d.phone}}</p>
                        <p>状态:{{d.status}}</p>
                        {{# } }}
                    </script>

                    <script type="text/html" id="info">
                        {{# if(d.count){ }}
                        <h4>付费订阅<font color="red">{{d.count}}</font>次</h4>
                        {{# } }}
                        {{# if(d.sum){ }}
                        <h4>共支付额<font color="red">{{d.sum}}</font>元</h4>
                        {{# } }}
                    </script>

                    <!--操作-->
                    <script type="text/html" id="act">
                        <button type="button" class="layui-btn layui-btn-xs" onclick="dropdown(this)">操作 <span class="caret"></span></button>
                        <ul class="layui-nav-child layui-anim layui-anim-upbit">
                            <li>
                                <a href="javascript:void(0);" class="" onclick="$eb.createModalFrame(this.innerText,'{:Url('edit')}?id={{d.id}}',{h:800,w:1000})">
                                    <i class="fa fa-pencil"></i> 查看基本信息</a>
                            </li>
                            <li>
                                <a href="javascript:void(0);" class="" onclick="$eb.createModalFrame(this.innerText,'{:Url('edit_content')}?id={{d.id}}',{h:800,w:1000})">
                                    <i class="fa fa-pencil"></i> 编辑求职优势</a>
                            </li>
                            <li>
                                <a href="javascript:void(0);" class="" onclick="$eb.createModalFrame(this.innerText,'{:Url('expectList')}?id={{d.id}}',{h:800,w:1000})">
                                    <i class="fa fa-eye"></i> 查看求职期望</a>
                            </li>
                            <li>
                                <a href="javascript:void(0);" class="" onclick="$eb.createModalFrame(this.innerText,'{:Url('workList')}?id={{d.id}}',{h:800,w:1000})">
                                    <i class="fa fa-eye"></i> 查看工作经历</a>
                            </li>
                            <li>
                                <a href="javascript:void(0);" class="" onclick="$eb.createModalFrame(this.innerText,'{:Url('educationList')}?id={{d.id}}',{h:800,w:1000})">
                                    <i class="fa fa-eye"></i> 查看教育经历</a>
                            </li>
                        </ul>

                        <button type="button" class="layui-btn layui-btn-xs" lay-event='delstor'><i class="fa fa-trash"></i> 删除</button>
                    </script>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="{__ADMIN_PATH}js/layuiList.js"></script>
<script>
    var type=1;
    //实例化form
    layList.form.render();
    //加载列表
    layList.tableList('List',"{:Url('get_list')}",function (){
        var join=new Array();
        switch (parseInt(type)){
            case 1:case 3:case 4:case 5:
                join=[
                    {field: 'id', title: 'ID', sort: true,event:'id',width:'6%',align:'center'},
                    {field: 'image', title: '头像',templet:'#image',width:'8%',align:'center'},
                    {field: 'store_name', title: '求职信息',templet:'#store_name'},
                    {field: 'info', title: '简历情况',templet:'#info'},
                    {field: 'add_time', title: '发布时间',templet:'#add_time',width:'8%',align:'center'},
                    {field: 'sort', title: '排序置顶',edit:'sort',width:'6%',align:'center'},
                    {field: 'status', title: '状态',templet:"#checkboxstatus",width:'8%',align:'center'},
                    {field: 'right', title: '操作',align:'center',toolbar:'#act',width:'10%'},
                ];
                break;
        }
        return join;
    })
    //excel下载
    layList.search('export',function(where){
        location.href=layList.U({c:'resume.resume',a:'get_list',q:{
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
        console.log(obj.field)
        switch (obj.field) {
            case 'sort':
                action.set_product('sort',id,value);
                break;
            case 'views':
                action.set_product('views',id,value);
                break;
        }
    });
    //上下加产品
    layList.switch('is_show',function (odj,value) {
        if(odj.elem.checked==true){
            layList.baseGet(layList.Url({c:'resume.resume',a:'set_show',p:{is_show:1,id:value}}),function (res) {
                layList.msg(res.msg);
            });
        }else{
            layList.baseGet(layList.Url({c:'resume.resume',a:'set_show',p:{is_show:0,id:value}}),function (res) {
                layList.msg(res.msg);
            });
        }
    });
    //点击事件绑定
    layList.tool(function (event,data,obj) {
        switch (event) {
            case 'delstor':
                var url=layList.U({c:'resume.resume',a:'delete',q:{id:data.id}});
                if(data.is_del) var code = {title:"操作提示",text:"确定恢复操作吗？",type:'info',confirm:'是的，恢复该'};
                else var code = {title:"操作提示",text:"确定将该删除吗？",type:'info',confirm:'是的，删除'};
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
            layList.baseGet(layList.Url({c:'resume.resume',a:'set_product',q:{field:field,id:id,value:value}}),function (res) {
                layList.msg(res.msg);
            });
        },
        show:function(){
            var ids=layList.getCheckData().getIds('id');
            if(ids.length){
                layList.basePost(layList.Url({c:'resume.resume',a:'product_show'}),{ids:ids},function (res) {
                    layList.msg(res.msg);
                    layList.reload();
                });
            }else{
                layList.msg('请选择要上架的信息');
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
