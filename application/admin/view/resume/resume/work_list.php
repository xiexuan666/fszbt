{extend name="public/container"}
{block name="content"}
<div class="row">
    <div class="col-sm-12">
        <div class="ibox float-e-margins">
            <div class="ibox-content">
                <div class="table-responsive">
                    <table class="table table-striped  table-bordered">
                        <thead>
                        <tr>
                            <th>编号</th>
                            <th>就职公司名称</th>
                            <th>开始时间</th>
                            <th>结束时间</th>
                            <th>就职部门</th>
                            <th>就职岗位</th>
                            <th>工作内容</th>
                        </tr>
                        </thead>
                        <tbody class="">
                        {volist name="list" id="vo"}
                        <tr>
                            <td class="text-center">
                                {$vo.id}
                            </td>
                            <td class="text-center">
                                {$vo.name}
                            </td>
                            <td class="text-center">
                                {$vo.start_time}
                            </td>
                            <td class="text-center">
                                {$vo.stop_time}
                            </td>
                            <td class="text-center">
                                {$vo.industry}
                            </td>
                            <td class="text-center">
                                {$vo.position}
                            </td>
                            <td class="text-center">
                                {$vo.description}
                            </td>
                        </tr>
                        {/volist}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
{/block}
