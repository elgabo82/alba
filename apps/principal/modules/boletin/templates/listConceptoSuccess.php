<?php use_helper("I18N")?>
<script>
     function linkTo(flag) {
        var objd = document.getElementById('division_id');
        var objp = document.getElementById('periodo_id');
        var objc = document.getElementById('carrera_id');

        var url  = "<?php echo url_for('boletin/', false);?>/listConcepto/";
        if(flag == 2) {
            url = url + "carrera_id/"+objc.options[objc.selectedIndex].value;
        }

        if(flag == 0) {
            url = url + "division_id/"+objd.options[objd.selectedIndex].value;
            url = url + "/carrera_id/"+objc.options[objc.selectedIndex].value;
        }

        if(flag == 1) {
            url = url + "division_id/"+objd.options[objd.selectedIndex].value;
            url = url + "/carrera_id/"+objc.options[objc.selectedIndex].value;
            url = url + "/periodo_id/"+objp.options[objp.selectedIndex].value;
        }

        var obja = document.getElementById('concepto_id');
        url = url + "/concepto_id/"+obja.options[obja.selectedIndex].value;
        location.href = url;
     }
</script>

<div id="sf_admin_container">
<h1>Notas de Concepto del Bolet&iacute;n</h1>

<?php echo form_tag('boletin/grabarNotasConcepto', 'id=sf_admin_edit_form name=sf_admin_edit_form multipart=true') ?>

<fieldset id="sf_fieldset_none" class="">
    <div class="form-row">
        <?php echo label_for('carrera', __('Carrera:')) ?>
        <?php echo select_tag('carrera_id', options_for_select($optionsCarrera, $carrera_id), "onChange='linkTo(2)'") ?>
    </div>
    <div class="form-row">
        <?php echo label_for('division', __('Division:')) ?>
        <?php echo select_tag('division_id', options_for_select($optionsDivision, $division_id), "onChange='linkTo(0)'") ?>
    </div>
    <div class="form-row">
        <?php echo label_for('concepto', __('Concepto:')) ?>
        <?php echo select_tag('concepto_id', options_for_select($optionsConcepto, $concepto_id),"onChange='linkTo(1)'") ?>
    </div>

<?php if($division_id) { ?>
    <div class="form-row">
        <?php echo label_for('perido', __('Periodo:')) ?>
        <?php echo select_tag('periodo_id', options_for_select($optionsPeriodo, $periodo_id), "onChange='linkTo(1)'") ?>
    </div>
<?php } ?>

<?php if (count($aAlumno) > 0 && $concepto_id ){ ?>
<h1>Alumnos</h1>

Posibles Notas para calificar: 
<?php foreach ($aPosiblesNotas as $posiblesnotas) { ?>
<?php echo $posiblesnotas->getNombre()."&nbsp;";?>
<?php } ?>

<table cellspacing="0" class="sf_admin_list">
  <thead>
  <tr>
    <th id="sf_admin_list_th_alumno"> Alumno</th>
    <?php foreach ($aPeriodo as $periodo) {?>
    <th id="sf_admin_list_th_sf_actions"><?php echo $periodo->getDescripcion()?></th>
    <?php } ?>
  </tr>
  </thead>

  <tbody>
<?php foreach($aAlumno as $alumno){ ?>
  <tr class="sf_admin_row_0">
    <td><?php echo $alumno->getApellido()." ".$alumno->getNombre(); ?></td>
    <?php foreach ($aPeriodo as $periodo) {?>    
    <td>
    <?php echo input_tag("nota[".$alumno->getId()."][".$periodo->getId()."]", $aNotaAlumno[$alumno->getId()][$periodo->getId()], array('size' => $sizeNota, 'maxlength' => $sizeNota));?><br>   
    <?php echo input_tag("notaObs[".$alumno->getId()."][".$periodo->getId()."]", $aNotaAlumnoObs[$alumno->getId()][$periodo->getId()], array('size' => 30, 'maxlength' => 128 ));?>
    </td>
    <?php } ?>
  </tr>
  <?php } ?>
  </tbody>
</table>

<?php if($division_id) { ?>

 <ul class="sf_admin_actions">
  <li><?php echo submit_tag(__('Grabar'), array (
  'name' => 'grabar',
  'class' => 'sf_admin_action_save',
)) ?></li>
</ul>
<?php }
 }
 ?>
</fieldset>
</form>
</div>

