{include file="sections/header.tpl"}

<form class="form-horizontal" method="post" role="form" action="{$_url}plugin/mikrotik_import_start_ui">
    <div class="row">
        <div class="col-sm-12 col-md-12">
            <div class="panel panel-primary panel-hovered panel-stacked mb30">
                <div class="panel-heading">Information</div>
                <div class="panel-body">
                    <ol>
                        <li>This Plugin only import Packages and Users</li>
                        <li>Active package will not be imported</li>
                        <li>You must Refill the user or User buy new package</li>
                    </ol>
                </div>
            </div>
            <div class="panel panel-primary panel-hovered panel-stacked mb30">
                <div class="panel-heading">Import User and Packages from Mikrotik</div>
                <div class="panel-body">
                    <div class="form-group">
                        <label class="col-md-2 control-label">{$_L['Type']}</label>
                        <div class="col-md-6">
                            <input type="radio" id="Hot" name="type" value="Hotspot"> {$_L['Hotspot_Plans']}
                            <input type="radio" id="POE" name="type" value="PPPOE"> {$_L['PPPOE_Plans']}
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">{$_L['Routers']}</label>
                        <div class="col-md-6">
                            <select id="server" required name="server" class="form-control">
                                <option value=''>{$_L['Select_Routers']}</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-lg-offset-2 col-lg-10">
                            <button class="btn btn-success waves-effect waves-light" type="submit">Import User</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

{include file="sections/footer.tpl"}
