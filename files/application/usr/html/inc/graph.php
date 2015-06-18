<?php
//  original code: jens.poenisch@isym.tu-chemnitz.de
class FunctionGraph {
  var $x0;
  var $y0;
  var $x1;
  var $y1;
  var $posX0;
  var $posY0;
  var $scale;
  var $img;
  var $wte;
  var $blk;
  var $grn;

  function FunctionGraph( $x1 = 2, $y1 = 2)
  {
    $width = 800;
    $height = 600;
    $this->x0 = -$x1;
    $this->y0 = -$y1;
    $this->x1 = $x1;
    $this->y1 = $y1;
    $this->posX0 = $width/2;
    $this->posY0 = $height/2;
    $this->scale = (double)($width-20)/($this->x1-$this->x0);
    $this->img = imageCreate($width, $height);
    $this->wte = imageColorAllocate($this->img, 255, 255, 255);
    $this->blk = imageColorAllocate($this->img, 0, 0, 0);
    $this->gry = imageColorAllocate($this->img, 100, 100, 100);
    $this->grn = imageColorAllocate($this->img, 0, 150, 0);
    $this->blu = imageColorAllocate($this->img, 0, 0, 150);
  }

  function drawAxes() {
    imagesetstyle($this->img, array($this->gry, $this->wte, $this->wte, $this->wte, $this->wte) );
    imageLine($this->img, $this->posX0 + $this->x0*$this->scale-2,
                          $this->posY0,
                          $this->posX0 + $this->x1*$this->scale+2,
              $this->posY0, $this->blk);
    imageLine($this->img, $this->posX0,
                          $this->posY0 - $this->y0*$this->scale+2,
                          $this->posX0,
              $this->posY0 - $this->y1*$this->scale-2, $this->blk);
    for ($x = 1; $x <= $this->x1; $x += 1) {
      imageline($this->img, $this->posX0+$x*$this->scale,0,$this->posX0+$x*$this->scale,$this->posY0 * 2, IMG_COLOR_STYLED);
      imageline($this->img, $this->posX0-$x*$this->scale,0,$this->posX0-$x*$this->scale,$this->posY0 * 2, IMG_COLOR_STYLED);

      imageLine($this->img, $this->posX0+$x*$this->scale,
                            $this->posY0-3,
                        $this->posX0+$x*$this->scale,
                $this->posY0+3, $this->blk);
      imageLine($this->img, $this->posX0-$x*$this->scale,
                            $this->posY0-3,
                        $this->posX0-$x*$this->scale,
                $this->posY0+3, $this->blk);
      imagestring($this->img, 2, $this->posX0+$x*$this->scale, $this->posY0+4, $x, $this->blu);
      imagestring($this->img, 2, $this->posX0-$x*$this->scale, $this->posY0+4, "-$x", $this->blu);
    }
    for ($y = 1; $y <= $this->y1; $y += 1) {
      imageline($this->img, 0, $this->posY0+$y*$this->scale,$this->posX0 * 2,$this->posY0+$y*$this->scale, IMG_COLOR_STYLED);
      imageline($this->img, 0, $this->posY0-$y*$this->scale,$this->posX0 * 2,$this->posY0-$y*$this->scale, IMG_COLOR_STYLED);

      imageLine($this->img, $this->posX0-3,
                            $this->posY0-$y*$this->scale,
                            $this->posX0+3,
                $this->posY0-$y*$this->scale, $this->blk);
      imageLine($this->img, $this->posX0-3,
                            $this->posY0+$y*$this->scale,
                            $this->posX0+3,
                $this->posY0+$y*$this->scale, $this->blk);
      imagestring($this->img, 2, $this->posX0+4, $this->posY0-$y*$this->scale, $y, $this->blu);
      imagestring($this->img, 2, $this->posX0+4, $this->posY0+$y*$this->scale, "-$y", $this->blu);
    }
  }

  function drawFunction($function, $dx = 0.1) {
    $xold = $x = $this->x0;
    eval("\$yold=".$function.";");
    for ($x += $dx; $x <= $this->x1; $x += $dx) {
      eval("\$y = ".$function.";");
      imageLine($this->img, $this->posX0+$xold*$this->scale,
                            $this->posY0-$yold*$this->scale,
                            $this->posX0+$x*$this->scale,
                $this->posY0-$y*$this->scale, $this->grn);
      $xold = $x;
      $yold = $y;
    }
  }

  function writePNG() {
    imagePNG($this->img);
  }

  function destroy() {
    imageDestroy($this->img);
  }
}
?>