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
                                <label class="layui-form-label">关键字</label>
                                <div class="layui-input-block">
                                    <input type="text" name="title" class="layui-input" placeholder="请输入关键字">
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
                        列表[排序]可进行快速修改,双击或者单击进入编辑模式,失去焦点可进行自动保存
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    </div>
                    <div class="layui-btn-container">
                        <button class="layui-btn layui-btn-sm" onclick="$eb.createModalFrame(this.innerText,'{:Url('create')}',{h:700,w:1100})">添加供应商</button>
                    </div>
                    <table class="layui-hide" id="List" lay-filter="List"></table>
                    <!--图片-->
                    <script type="text/html" id="logo">
                        {{# if(d.logo){ }}
                        <img style="cursor: pointer" lay-event='open_image' src="{{d.logo}}">
                        {{# } }}
                    </script>
                    <!--上架|下架-->
                    <script type="text/html" id="is_show">
                        <input type='checkbox' name='id' lay-skin='switch' value="{{d.id}}" lay-filter='is_show' lay-text='显示|隐藏'  {{ d.is_show == 1 ? 'checked' : '' }}>
                    </script>
                    <!--推荐-->
                    <script type="text/html" id="is_top">
                        <input type='checkbox' name='id' lay-skin='switch' value="{{d.id}}" lay-filter='is_top' lay-text='是|否'  {{ d.is_top == 1 ? 'checked' : '' }}>
                    </script>
                    <!--产品名称-->
                    <script type="text/html" id="title">
                        <h4>
                            {{# if(d.ctitle!=''){ }}
                            {{d.ctitle}} &
                            {{# } }}
                            {{d.title}}
                        </h4>
                    </script>
                    <!--操作-->

                    <script type="text/html" id="act">
                        <button type="button" class="layui-btn layui-btn-xs layui-btn-normal" onclick="$eb.createModalFrame('{{d.name}}-编辑','{:Url('edit')}?id={{d.id}}',{h:700,w:1100})">
                            <i class="fa fa-edit"></i> 编辑
                        </button>
                        <button type="button" class="layui-btn layui-btn-xs" lay-event='delstor'><i class="fa fa-trash"></i> 删除</button>
                    </script>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="{__ADMIN_PATH}js/layuiList.js"></script>
<script>
    //实例化form
    layList.form.render();
    //加载列表
    layList.tableList('List',"{:Url('getList')}",function (){
        join=[
            {field: 'id', title: 'ID', sort: true,event:'id',width:'6%',align:'center'},
            {field: 'logo', title: 'logo',templet:'#logo',width:'10%',align:'center'},
            {field: 'name', title: '供应商名称',event:'name'},
            {field: 'ctitle', title: '所属分类',event:'ctitle'},
            {field: 'add_time', title: '发布时间',event:"add_time",align:'center',width:'8%',align:'center'},
            {field: 'sort', title: '排序', sort: true,align:'center',edit:'sort',width:'6%',align:'center'},
            {field: 'is_top', title: '置顶',templet:"#is_top",width:'6%',align:'center'},
            {field: 'is_show', title: '状态',templet:"#is_show",width:'8%',align:'center'},
            {field: 'right', title: '操作',align:'center',toolbar:'#act',width:'14%'},
        ];
        return join;
    })

    //下拉框
    $(document).click(function (e) {
        $('.layui-nav-child').hide();
    })

    //快速编辑
    layList.edit(function (obj) {
        var id=obj.data.id,value=obj.value;
        switch (obj.field) {
            case 'price':
                action.set_editor('price',id,value);
                break;
            case 'stock':
                action.set_editor('stock',id,value);
                break;
            case 'sort':
                action.set_editor('sort',id,value);
                break;
            case 'visit':
                action.set_editor('visit',id,value);
                break;
        }
    });

    //显示隐藏
    layList.switch('is_show',function (odj,value) {
        if(odj.elem.checked==true){
            layList.baseGet(layList.Url({c:'supplier.supplier',a:'set_show',p:{is_show:1,id:value}}),function (res) {
                layList.msg(res.msg);
            });
        }else{
            layList.baseGet(layList.Url({c:'supplier.supplier',a:'set_show',p:{is_show:0,id:value}}),function (res) {
                layList.msg(res.msg);
            });
        }
    });

    //是否置顶
    layList.switch('is_top',function (odj,value) {
        if(odj.elem.checked==true){
            layList.baseGet(layList.Url({c:'supplier.supplier',a:'set_top',p:{is_top:1,id:value}}),function (res) {
                layList.msg(res.msg);
            });
        }else{
            layList.baseGet(layList.Url({c:'supplier.supplier',a:'set_top',p:{is_top:0,id:value}}),function (res) {
                layList.msg(res.msg);
            });
        }
    });
    //点击事件绑定
    layList.tool(function (event,data,obj) {
        switch (event) {
            case 'delstor':
                var url=layList.U({c:'supplier.supplier',a:'delete',q:{id:data.id}});
                if(data.is_del) var code = {title:"操作提示",text:"确定恢复操作吗？",type:'info',confirm:'是的，恢复'};
                else var code = {title:"操作提示",text:"确定将该删除吗？",type:'info',confirm:'是的'};
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
        set_editor:function(field,id,value){
            layList.baseGet(layList.Url({c:'supplier.supplier',a:'set_editor',q:{field:field,id:id,value:value}}),function (res) {
                layList.msg(res.msg);
            });
        },
        show:function(){
            var ids=layList.getCheckData().getIds('id');
            if(ids.length){
                layList.basePost(layList.Url({c:'supplier.supplier',a:'product_show'}),{ids:ids},function (res) {
                    layList.msg(res.msg);
                    layList.reload();
                });
            }else{
                layList.msg('请选择');
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
