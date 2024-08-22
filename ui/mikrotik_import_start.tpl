{include file="sections/header.tpl"}

<form class="form-horizontal" method="post" role="form" action="{$_url}settings/app-post">
    <div class="row">
        <div class="col-sm-12 col-md-12">
            <div class="panel panel-primary panel-hovered panel-stacked mb30">
                <div class="panel-heading">{Lang::T('Information')}</div>
                <div class="panel-body">
                    {Lang::T('After import, you need to configure Packages, set time limit')}
                </div>
            </div>
            <div class="panel panel-primary panel-hovered panel-stacked mb30">
                <div class="panel-heading">{Lang::T('Package import')}</div>
                <div class="panel-body">
                    <ol>
                        {foreach $results as $result}
                            <li>{$result}</li>
                        {/foreach}
                    </ol>
                </div>
            </div>
        </div>
    </div>
</form>

{include file="sections/footer.tpl"}
