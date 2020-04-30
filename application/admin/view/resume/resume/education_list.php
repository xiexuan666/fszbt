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
                            <th>学校</th>
                            <th>学历</th>
                            <th>专业</th>
                            <th>开始时间</th>
                            <th>结束时间</th>
                            <th>在校经历</th>
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
                                {$vo.education}
                            </td>
                            <td class="text-center">
                                {$vo.professional}
                            </td>
                            <td class="text-center">
                                {$vo.start_time}
                            </td>
                            <td class="text-center">
                                {$vo.stop_time}
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
