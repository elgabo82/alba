<?php
/**
 *    This file is part of Alba.
 *
 *    Alba is free software; you can redistribute it and/or modify
 *    it under the terms of the GNU General Public License as published by
 *    the Free Software Foundation; either version 2 of the License, or
 *    (at your option) any later version.
 *
 *    Alba is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU General Public License for more details.
 *
 *    You should have received a copy of the GNU General Public License
 *    along with Alba; if not, write to the Free Software
 *    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */


/**
 * boletin actions
 *
 * @package    alba
 * @author     José Luis Di Biase <josx@interorganic.com.ar>
 * @author     Héctor Sanchez <hsanchez@pressenter.com.ar>
 * @author     Fernando Toledo <ftoledo@pressenter.com.ar>
 * @version    SVN: $Id$
 * @filesource
 * @license GPL
 */

class boletinActions extends sfActions
{

    public function preExecute() {
        $this->vista = $this->getRequestParameter('vista');
    }
  

    public function executeGrabarNotas() {

        // inicializando variables
        $aDatosTablaEscalaNota = array();

        // tomando los datos del formulario
        $division_id = $this->getRequestParameter('division_id');
        $actividad_id = $this->getRequestParameter('actividad_id');
        $periodo_id = $this->getRequestParameter('periodo_id');
        $aNota = $this->getRequestParameter('nota');

        $cantNotas = count($aNota);
        if($cantNotas > 0) {
            // tomo escala notas
            $establecimiento_id = $this->getUser()->getAttribute('fk_establecimiento_id');
            $aDatosTablaEscalaNota = $this->getEscalanota($establecimiento_id);

            //grabo al disco
            $con = Propel::getConnection();
            try {
                $con->begin();
                $criteria = new Criteria();

                foreach($aNota as $alumno_id => $aPeriodo ) {
                    foreach($aPeriodo as $periodoid => $nota) {
                        $cton1 = $criteria->getNewCriterion(BoletinActividadesPeer::FK_ALUMNO_ID, $alumno_id);
                        $cton2 = $criteria->getNewCriterion(BoletinActividadesPeer::FK_PERIODO_ID, $periodoid);
                        $cton3 = $criteria->getNewCriterion(BoletinActividadesPeer::FK_ACTIVIDAD_ID, $actividad_id);
                        $cton1->addAnd($cton2)->addAnd($cton3);
                        $criteria->addOr($cton1);
                    }
                }
                BoletinActividadesPeer::doDelete($criteria);

                foreach($aNota as $alumno_id => $aPeriodo ) {
                    foreach($aPeriodo as $periodoid => $nota) {
                        // estaria bueno hacer todos los insert en una sola query
                        $boletin = new BoletinActividades();
                        $boletin->setFkAlumnoId($alumno_id);
                        $boletin->setFkPeriodoId($periodoid);
                        $boletin->setFkActividadId($actividad_id);
                        if(array_key_exists($nota[0], $aDatosTablaEscalaNota)) {
                            $boletin->setFkEscalanotaId($aDatosTablaEscalaNota[$nota[0]]);
                        }
                        $boletin->setFecha(date("Y-m-d"));
                        $boletin->save();
                    }
                }

                $con->commit(); 
             }             
             catch (Exception $e){
                 $con->rollback();
                 throw $e;  
            }
        }
        return $this->redirect("boletin?action=list&division_id=$division_id&actividad_id=$actividad_id&periodo_id=$periodo_id");
    }   



    protected function getDivisiones($establecimiento_id) {
        $optionsDivision = array();
        $criteria = new Criteria();
        $criteria->add(AnioPeer::FK_ESTABLECIMIENTO_ID, $establecimiento_id);
        $divisiones = DivisionPeer::doSelectJoinAnio($criteria);
        $optionsDivision[]  = "";
        foreach($divisiones as $division) {
            $optionsDivision[$division->getId()] = $division->getAnio()->getDescripcion()." ".$division->getDescripcion();
        }
        asort($optionsDivision);
        return $optionsDivision;
    }

    protected function getAlumnos($division_id) {
        $aAlumno = array();
        $criteria = new Criteria();
        $criteria->add(DivisionPeer::ID, $division_id);
        $criteria->addJoin(RelAlumnoDivisionPeer::FK_ALUMNO_ID, AlumnoPeer::ID);
        $criteria->addJoin(RelAlumnoDivisionPeer::FK_DIVISION_ID, DivisionPeer::ID);
        $aAlumno = AlumnoPeer::doSelect($criteria);
        return $aAlumno;

    }

    protected function getActividades($division_id) {
        $optionsActividad = array();
        $criteria = new Criteria();
        $criteria->add(DivisionPeer::ID, $division_id);
        $criteria->addJoin(DivisionPeer::FK_ANIO_ID, AnioPeer::ID);
        $criteria->addJoin(RelAnioActividadPeer::FK_ANIO_ID, AnioPeer::ID);
        $criteria->addJoin(RelAnioActividadPeer::FK_ACTIVIDAD_ID, ActividadPeer::ID);
        $actividades = ActividadPeer::doSelect($criteria);
        foreach($actividades as $actividad) {
            $optionsActividad[$actividad->getId()] = $actividad->getNombre();
        }
        asort($optionsActividad);
        return $optionsActividad;
    }

    public function executeList() {

        // inicializando variables
        $optionsActividad = array();
        $optionsDivision = array();
        $aAlumno = array();    
        $division_id = "";
        $actividad_id = "";
        $periodo_id = "";
        $aPeriodo = array();
        $aPosiblesNotas = array();
        $optionsPeriodo = array();
        $aNotaAlumno = array();
        $sizeNota = 0;
    

        $establecimiento_id = $this->getUser()->getAttribute('fk_establecimiento_id');
        
        // llenando el combo de division segun establecimiento
        $optionsDivision = $this->getDivisiones($establecimiento_id);

        // tomando los datos del formulario
        $division_id = $this->getRequestParameter('division_id');
        $periodo_id = $this->getRequestParameter('periodo_id');

        if($division_id) {
            $actividad_id = $this->getRequestParameter('actividad_id');
            $optionsActividad = $this->getActividades($division_id);
        }

            $aAlumno = $this->getAlumnos($division_id);
            $criteria = new Criteria();
            $criteria->add(PeriodoPeer::FK_CICLOLECTIVO_ID, $this->getUser()->getAttribute('fk_ciclolectivo_id'));
            $aPeriodo = PeriodoPeer::doSelect($criteria);
            $optionsPeriodo[] = "";
            foreach($aPeriodo as $periodo) {
                $optionsPeriodo[$periodo->getId()] = $periodo->getDescripcion();
            }          

            if($periodo_id) {
                $aPeriodo = array();
                $aPeriodo[] = PeriodoPeer::retrieveByPK($periodo_id);
            }

           
            if(count($aAlumno) > 0) {
                // esto puede ser mejorado con solo una query bastante facilmente
                foreach($aAlumno as $alumno) {
                    foreach($aPeriodo as $periodo) {
                        $criteria = new Criteria();
                        $criteria->add(BoletinActividadesPeer::FK_ALUMNO_ID, $alumno->getId());
                        $criteria->add(BoletinActividadesPeer::FK_PERIODO_ID, $periodo->getId());
                        $criteria->add(BoletinActividadesPeer::FK_ACTIVIDAD_ID, $actividad_id);
                        $criteria->addJoin(BoletinActividadesPeer::FK_ESCALANOTA_ID, EscalanotaPeer::ID);
                        $criteria->addAsColumn("boletinActividades_periodo_id", BoletinActividadesPeer::FK_PERIODO_ID);
                        $criteria->addAsColumn("boletinActividades_id", BoletinActividadesPeer::ID);
                        $criteria->addAsColumn("escalanota_nombre", EscalanotaPeer::NOMBRE);
                        $criteria->addAsColumn("escalanota_id", EscalanotaPeer::ID);
                        $aBoletinActividades = BasePeer::doSelect($criteria);
                        $aNotaAlumno[$alumno->getId()][$periodo->getId()]  = "";
                        foreach($aBoletinActividades as $boletinActividades) {
                            $aNotaAlumno[$alumno->getId()][$periodo->getId()] = $boletinActividades[2];
                        }
                    }
                }
            }

            $criteria = new Criteria();
            $criteria->add(EscalanotaPeer::FK_ESTABLECIMIENTO_ID, $establecimiento_id);
            $aPosiblesNotas = EscalanotaPeer::doSelect($criteria);
            foreach($aPosiblesNotas as $p) {
                $actual = strlen($p->getNombre());
                if($actual > $sizeNota) {
                    $sizeNota = $actual;
                }
            }
     

        // llenar variables a mostrar en el template
        $this->optionsDivision = $optionsDivision;
        $this->optionsActividad =$optionsActividad;
        $this->aAlumno = $aAlumno;
        $this->division_id = $division_id;
        $this->actividad_id = $actividad_id;
        $this->periodo_id = $periodo_id;
        $this->aPeriodo = $aPeriodo;
        $this->aPosiblesNotas = $aPosiblesNotas;
        $this->optionsPeriodo = $optionsPeriodo;
        $this->aNotaAlumno = $aNotaAlumno;
        $this->sizeNota = $sizeNota;
    }


    protected function getConcepto($establecimiento_id) {
        $optionsConcepto = array();
        $criteria = new Criteria();
        $criteria->add(ConceptoPeer::FK_ESTABLECIMIENTO_ID, $establecimiento_id );
        $conceptos = ConceptoPeer::doSelect($criteria);
        foreach($conceptos as $concepto) {
            $optionsConcepto[$concepto->getId()] = $concepto->getNombre();
        }
        asort($optionsConcepto);
        return $optionsConcepto;
    }

    public function executeListConcepto() {
        // inicializando variables
        $optionsConcepto = array();
        $optionsDivision = array();
        $aAlumno = array();    
        $division_id = "";
        $concepto_id = "";
        $periodo_id = "";
        $aPeriodo = array();
        $aPosiblesNotas = array();
        $optionsPeriodo = array();
        $aNotaAlumno = array();
        $aNotaAlumnoObs = array();
        $sizeNota = 0;

        $establecimiento_id = $this->getUser()->getAttribute('fk_establecimiento_id');
        
        // llenando el combo de division segun establecimiento
        $optionsDivision = $this->getDivisiones($establecimiento_id);

        // tomando los datos del formulario
        $division_id = $this->getRequestParameter('division_id');
        $periodo_id = $this->getRequestParameter('periodo_id');
        $concepto_id = $this->getRequestParameter('concepto_id');
             
        $optionsConcepto [] = "";
        $optionsConcepto = array_merge($optionsConcepto,$this->getConcepto($establecimiento_id));

        
        $aAlumno = $this->getAlumnos($division_id);

        $criteria = new Criteria();
        $criteria->add(PeriodoPeer::FK_CICLOLECTIVO_ID, $this->getUser()->getAttribute('fk_ciclolectivo_id'));
        $aPeriodo = PeriodoPeer::doSelect($criteria);
        $optionsPeriodo[] = "Todos";
        foreach($aPeriodo as $periodo) {
            $optionsPeriodo[$periodo->getId()] = $periodo->getDescripcion();
        }          

        if($periodo_id) {
            $aPeriodo = array();
            $aPeriodo[] = PeriodoPeer::retrieveByPK($periodo_id);
        }

        if(count($aAlumno) > 0) {
// esto puede ser mejorado con solo una query bastante facilmente
            foreach($aAlumno as $alumno) {
                foreach($aPeriodo as $periodo) {
                    $criteria = new Criteria();
                    $criteria->add(BoletinConceptualPeer::FK_ALUMNO_ID, $alumno->getId());
                    $criteria->add(BoletinConceptualPeer::FK_PERIODO_ID, $periodo->getId());
                    $criteria->add(BoletinConceptualPeer::FK_CONCEPTO_ID, $concepto_id);
                    $aBoletinConceptual = BoletinConceptualPeer::doSelect($criteria);
                    $aNotaAlumno[$alumno->getId()][$periodo->getId()]  = "";
                    $aNotaAlumnoObs[$alumno->getId()][$periodo->getId()]  = "";
                    foreach($aBoletinConceptual as $boletinConceptual ) {
//if(method_exists($legajopedagogico->getResumen(),'getContents')) {
                        if($boletinConceptual->getFkEscalanotaId()) {
                            $aNotaAlumno[$alumno->getId()][$periodo->getId()] = $boletinConceptual->getEscalanota()->getNombre();
                        }
                        if($boletinConceptual->getObservacion()->getContents()) {
                            $aNotaAlumnoObs[$alumno->getId()][$periodo->getId()] = $boletinConceptual->getObservacion()->getContents();
                        }
                    }
                }
            }

        }

        $criteria = new Criteria();
        $criteria->add(EscalanotaPeer::FK_ESTABLECIMIENTO_ID, $establecimiento_id);
        $aPosiblesNotas = EscalanotaPeer::doSelect($criteria);
        foreach($aPosiblesNotas as $p) {
            $actual = strlen($p->getNombre());
            if($actual > $sizeNota) {
                $sizeNota = $actual;
            }
        }

        // llenar variables a mostrar en el template
        $this->optionsDivision = $optionsDivision;
        $this->optionsConcepto =$optionsConcepto;
        $this->aAlumno = $aAlumno;
        $this->division_id = $division_id;
        $this->concepto_id = $concepto_id;
        $this->periodo_id = $periodo_id;
        $this->aPeriodo = $aPeriodo;
        $this->aPosiblesNotas = $aPosiblesNotas;
        $this->optionsPeriodo = $optionsPeriodo;
        $this->aNotaAlumno = $aNotaAlumno;
        $this->aNotaAlumnoObs = $aNotaAlumnoObs;
        $this->sizeNota = $sizeNota;
    }


    public function executeIndex() {
       return $this->forward('boletin', 'list');
    }

    public function executeMostrar() {
        // Inicializar variables
        $optionsConcepto = array();
        $optionsPeriodo = array();
        $optionsActividad = array();
        $alumno = "";
        $division = "";
        $alumno_id = "";
        $division_id = "";
        $notaAlumno = array();
        $conceptoAlumno = array();
        $aAsistencia = array();

        // vars del formulario
        $alumno_id = $this->getRequestParameter('alumno_id');
        $division_id = $this->getRequestParameter('division_id');
        $establecimiento_id = $this->getUser()->getAttribute('fk_establecimiento_id');
        $no_cargar = 0;

        if($alumno_id) {
            $alumno = AlumnoPeer::retrieveByPK($alumno_id);

            if(!$division_id) {
                $c = new Criteria();
                $c->add(RelAlumnoDivisionPeer::FK_ALUMNO_ID, $alumno_id);
                $ad = RelAlumnoDivisionPeer::doSelectOne($c);
                if($ad) {
                    $division_id = $ad->getFkDivisionId();
                } else {
                    $no_cargar = 1;    
                }
            }

            if($no_cargar == 0) {

                $division = DivisionPeer::retrieveByPK($division_id);
                $optionsActividad = $this->getActividades($division_id);
                $optionsConcepto = $this->getConcepto($establecimiento_id);

                // notas del alumno
                $criteria = new Criteria();
                $criteria->add(BoletinActividadesPeer::FK_ALUMNO_ID, $alumno->getId());
                $criteria->addJoin(BoletinActividadesPeer::FK_ESCALANOTA_ID, EscalanotaPeer::ID);
                $criteria->addAsColumn("boletinActividades_periodo_id", BoletinActividadesPeer::FK_PERIODO_ID);
                $criteria->addAsColumn("boletinActividades_actividad_id", BoletinActividadesPeer::FK_ACTIVIDAD_ID);
                $criteria->addAsColumn("escalanota_nombre", EscalanotaPeer::NOMBRE);
                $aBoletinActividades = BasePeer::doSelect($criteria);
                foreach($aBoletinActividades as $boletinActividades) {
                    $notaAlumno[$boletinActividades[0]][$boletinActividades[1]] = $boletinActividades[2];
                }


                //conceptos de alumno
                $criteria = new Criteria();
                $criteria->add(BoletinConceptualPeer::FK_ALUMNO_ID, $alumno->getId());
                $aBoletinConceptual = BoletinConceptualPeer::doSelect($criteria);
                foreach($aBoletinConceptual as $boletinConceptual ) {
                    if($boletinConceptual->getFkEscalanotaId()) {
                        $conceptoAlumno[$boletinConceptual->getFkPeriodoId()][$boletinConceptual->getFkConceptoId()] = $boletinConceptual->getEscalanota()->getNombre();
                    }
                    if($boletinConceptual->getObservacion()->getContents()) {
                        $conceptoAlumno[$boletinConceptual->getFkPeriodoId()][$boletinConceptual->getFkConceptoId()] = $boletinConceptual->getObservacion()->getContents();
                    }
                }            

                $criteria = new Criteria();
                $criteria->add(PeriodoPeer::FK_CICLOLECTIVO_ID, $this->getUser()->getAttribute('fk_ciclolectivo_id'));
                $aPeriodo = PeriodoPeer::doSelect($criteria);
                foreach($aPeriodo as $periodo) {
                    $optionsPeriodo[$periodo->getId()] = $periodo->getDescripcion();
                    $aAsistencia[$periodo->getId()] = $this->getAsistenciaTotal($alumno_id, $periodo->getFechaInicio(), $periodo->getFechaFin());
                }       
            } else {
                $this->setFlash('notice','Error: el alumno no esta en ninguna división');
            }
        } else {
            $this->setFlash('notice','Error: no envio el alumno');
        }


        // variables al template
        $this->optionsPeriodo = $optionsPeriodo;
        $this->optionsActividad = $optionsActividad;
        $this->cantOptionsActividad = count($optionsActividad);
        $this->alumno = $alumno;
        $this->division = $division;
        $this->optionsConcepto = $optionsConcepto;
        $this->cantOptionsConcepto = count($optionsConcepto);
        $this->notaAlumno = $notaAlumno;
        $this->conceptoAlumno = $conceptoAlumno;
        $this->aAsistencia = $aAsistencia;
        $this->cantOptionsAsistencia = (count($aAsistencia)>0)?count(current($aAsistencia)):0;
    }

    protected function getEscalanota($establecimiento_id) {
        $aDatosTablaEscalaNota = array();
        $criteria = new Criteria();
        $criteria->add(EscalanotaPeer::FK_ESTABLECIMIENTO_ID, $establecimiento_id);
        $aEscalanota = EscalanotaPeer::doSelect($criteria);
        foreach($aEscalanota as $escalanota) {
            $aDatosTablaEscalaNota[$escalanota->getNombre()] = $escalanota->getId();
        }
        return $aDatosTablaEscalaNota;
    }

    public function executeGrabarNotasConcepto() {

        // inicializando variables
        $aDatosTablaEscalaNota = array();

        // tomando los datos del formulario
        $division_id = $this->getRequestParameter('division_id');
        $concepto_id = $this->getRequestParameter('concepto_id');
        $periodo_id = $this->getRequestParameter('periodo_id');
        $aNota = $this->getRequestParameter('nota');
        $aNotaObs = $this->getRequestParameter('notaObs');

        $cantNotas = count($aNota);
        if($cantNotas > 0) {
            // tomo escala notas
            $establecimiento_id = $this->getUser()->getAttribute('fk_establecimiento_id');

            $aDatosTablaEscalaNota = $this->getEscalanota($establecimiento_id);
 
            //grabo al disco
            $con = Propel::getConnection();
            try {
                $con->begin();
                $criteria = new Criteria();

                foreach($aNota as $alumno_id => $aPeriodo ) {
                    foreach($aPeriodo as $periodoid => $nota) {
                        $cton1 = $criteria->getNewCriterion(BoletinConceptualPeer::FK_ALUMNO_ID, $alumno_id);
                        $cton2 = $criteria->getNewCriterion(BoletinConceptualPeer::FK_PERIODO_ID, $periodoid);
                        $cton3 = $criteria->getNewCriterion(BoletinConceptualPeer::FK_CONCEPTO_ID, $concepto_id);
                        $cton1->addAnd($cton2)->addAnd($cton3);
                        $criteria->addOr($cton1);
                    }
                }
                BoletinActividadesPeer::doDelete($criteria);

                foreach($aNota as $alumno_id => $aPeriodo ) {
                    foreach($aPeriodo as $periodoid => $nota) {
                        // estaria bueno hacer todos los insert en una sola query
                        $boletin = new BoletinConceptual();
                        $boletin->setFkAlumnoId($alumno_id);
                        $boletin->setFkPeriodoId($periodoid);
                        $boletin->setFkConceptoId($concepto_id);
                        if($nota) {
                             if(array_key_exists($nota, $aDatosTablaEscalaNota)) {
                                 $boletin->setFkEscalanotaId($aDatosTablaEscalaNota[$nota]);
                             }
                        }
                        if($aNotaObs[$alumno_id][$periodoid]) {
                            $boletin->setObservacion($aNotaObs[$alumno_id][$periodoid]);
                        }
                        $boletin->setFecha(date("Y-m-d"));
                        $boletin->save();
                    }
                }
                $con->commit(); 
             }             
             catch (Exception $e){
                 $con->rollback();
                 throw $e;  
            }
        }
        return $this->redirect("boletin?action=listConcepto&division_id=$division_id&concepto_id=$concepto_id&periodo_id=$periodo_id");
    }   


    public function getAsistenciaTotal($alumno_id, $fecha_inicio, $fecha_fin) {
        $aAsistencia = array();
        
        $con = sfContext::getInstance()->getDatabaseConnection($connection='propel');
        // consulta muy fea que supongo que se puede hacer mas elegante y simple
        $sql = "
(
 SELECT ta.grupo as grupo, 0 as valor, asistencia.id as id
 FROM tipoasistencia ta
 LEFT JOIN asistencia ON  asistencia.fk_tipoasistencia_id = ta.id AND asistencia.fk_alumno_id = %s AND asistencia.fecha >= '%s' AND asistencia.fecha <= '%s'
 GROUP BY ta.grupo
 HAVING asistencia.id IS NULL
 )
 UNION
 (
 SELECT ta.grupo as grupo,SUM(ta.valor) as valor, a.id as id
 FROM tipoasistencia ta, asistencia a
 WHERE a.fk_tipoasistencia_id = ta.id  AND a.fk_alumno_id = %s AND a.fecha >= '%s' AND a.fecha <= '%s'
 GROUP BY ta.grupo
 )
                ";
        $stmt = $con->createStatement();
        $aDatos = $stmt->executeQuery(sprintf($sql,$alumno_id, $fecha_inicio, $fecha_fin, $alumno_id, $fecha_inicio, $fecha_fin), ResultSet::FETCHMODE_ASSOC);        
        foreach($aDatos as $dato) {
            $aAsistencia[$dato['grupo']] = $dato['valor'];
        }

        return $aAsistencia;
    }

}
