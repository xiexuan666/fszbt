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
                            <th>期望工作部门</th>
                            <th>期望职位</th>
                            <th>期望城市</th>
                            <th>薪资要求</th>
                        </tr>
                        </thead>
                        <tbody class="">
                        {volist name="list" id="vo"}
                        <tr>
                            <td class="text-center">
                                {$vo.id}
                            </td>
                            <td class="text-center">
                                {$vo.industry}
                            </td>
                            <td class="text-center">
                                {$vo.position}
                            </td>
                            <td class="text-center">
                                {$vo.city} · {$vo.district}
                            </td>
                            <td class="text-center">
                                {$vo.salary}
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