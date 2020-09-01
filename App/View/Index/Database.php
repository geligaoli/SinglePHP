<include 'Public/header'/>
<div class="container">
    <h1>获取一条记录</h1>
    <p>{:print_r($result1)}</p>

    <h1>获取三条记录</h1>
    <if><each "$result3 as $row">
        <each "$row as $key=>$val"> {:$val} <if "$row['id'] > 20">|<else/>#</if>
        </each>
        <br/>
    </each></p>

    <h1>分页查询</h1>
    <p><each "$result as $item">
        {:print_r($item)}<br/>
        </each></p>

</div>
<include 'Public/footer'/>
