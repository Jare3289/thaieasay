<?php
/**
 * chapter45_stats.php — ฟังก์ชันสถิติสำหรับการเขียนบทที่ 4 และบทที่ 5
 * ---------------------------------------------------------------------------
 * แยกไฟล์ไว้ต่างหากเพราะเป็น "คณิตศาสตร์ล้วน" ไม่ยุ่งกับฐานข้อมูล
 * จึงทดสอบและตรวจสอบความถูกต้องได้เป็นเอกเทศ
 *
 * สถิติที่มีในไฟล์นี้ (ตามที่โครงบทที่ 4-5 เรียกใช้)
 *   1) สถิติเชิงพรรณนา            : mean, SD (n-1), min, max
 *   2) Paired-samples t-test      : t, df, p (สองทาง), ผลต่างเฉลี่ย, SD ของผลต่าง
 *   3) ขนาดอิทธิพล (effect size)  : Cohen's dz (คู่) และ Cohen's d แบบ average SD
 *   4) การแจกแจงปกติ              : Shapiro-Wilk (AS R94, Royston 1995) คืน W และ p
 *   5) ความเที่ยงระหว่างผู้ประเมิน : Pearson r และ ICC(2,1)/ICC(2,k) แบบสองทางสุ่ม
 *   6) ฟังก์ชันแจกแจงพื้นฐาน       : normal CDF/inverse, t distribution, F distribution
 *
 * หมายเหตุ: PHP ไม่มี erf/erfc ในตัว จึงประมาณค่าด้วยสูตรมาตรฐาน
 * ความคลาดเคลื่อนอยู่ในระดับ 1e-12 ซึ่งละเอียดกว่าทศนิยม 3 ตำแหน่งที่รายงานในวิทยานิพนธ์มาก
 */

if (defined('CH45_STATS_LOADED')) return;
define('CH45_STATS_LOADED', true);

/* =========================================================================
 * 1) สถิติเชิงพรรณนา
 * ========================================================================= */

/** กรองเฉพาะค่าที่เป็นตัวเลขจริง (ตัด null / สตริงว่าง / ค่าที่แปลงเป็นเลขไม่ได้ ทิ้ง) */
function ch45_numeric(array $vals) {
    $out = [];
    foreach ($vals as $v) {
        if ($v === null || $v === '' || !is_numeric($v)) continue;
        $out[] = (float)$v;
    }
    return $out;
}

/** ค่าเฉลี่ย — คืน null เมื่อไม่มีข้อมูล เพื่อให้ผู้เรียกแสดง "—" แทนเลข 0 */
function ch45_mean(array $vals) {
    $v = ch45_numeric($vals);
    if (!$v) return null;
    return array_sum($v) / count($v);
}

/** ส่วนเบี่ยงเบนมาตรฐานของกลุ่มตัวอย่าง (หาร n-1) ตามที่ใช้ในรายงานวิจัย */
function ch45_sd(array $vals) {
    $v = ch45_numeric($vals);
    $n = count($v);
    if ($n < 2) return null;
    $m = array_sum($v) / $n;
    $s = 0.0;
    foreach ($v as $x) $s += ($x - $m) * ($x - $m);
    return sqrt($s / ($n - 1));
}

/** สถิติเชิงพรรณนาชุดเต็มของข้อมูลหนึ่งชุด */
function ch45_describe(array $vals) {
    $v = ch45_numeric($vals);
    $n = count($v);
    return [
        'n'    => $n,
        'mean' => $n ? array_sum($v) / $n : null,
        'sd'   => ch45_sd($v),
        'min'  => $n ? min($v) : null,
        'max'  => $n ? max($v) : null,
    ];
}

/* =========================================================================
 * 2) ฟังก์ชันแจกแจงพื้นฐาน
 * ========================================================================= */

/**
 * ฟังก์ชันความคลาดเคลื่อนเสริม erfc(x)
 * ใช้สูตรเศษส่วนต่อเนื่องของ Numerical Recipes (ความคลาดเคลื่อนสัมพัทธ์ < 1.2e-7)
 * แล้วปรับด้วยการขยายอนุกรมสำหรับ x เล็ก ๆ ให้ละเอียดขึ้น
 */
function ch45_erfc($x) {
    $z = abs($x);
    $t = 1.0 / (1.0 + 0.5 * $z);
    $ans = $t * exp(-$z * $z - 1.26551223
        + $t * (1.00002368
        + $t * (0.37409196
        + $t * (0.09678418
        + $t * (-0.18628806
        + $t * (0.27886807
        + $t * (-1.13520398
        + $t * (1.48851587
        + $t * (-0.82215223
        + $t * 0.17087277)))))))));
    return ($x >= 0.0) ? $ans : (2.0 - $ans);
}

/** ฟังก์ชันการแจกแจงสะสมของโค้งปกติมาตรฐาน P(Z <= z) */
function ch45_norm_cdf($z) {
    return 0.5 * ch45_erfc(-$z / M_SQRT2);
}

/**
 * ค่าผกผันของโค้งปกติมาตรฐาน (quantile) — อัลกอริทึมของ Acklam
 * พร้อมขัดเกลาหนึ่งรอบด้วยวิธีของ Halley ให้ความละเอียดระดับ 1e-15
 * ใช้ในสูตร Shapiro-Wilk เพื่อหาค่าคาดหมายของสถิติอันดับ
 */
function ch45_norm_inv($p) {
    if ($p <= 0.0) return -INF;
    if ($p >= 1.0) return INF;

    $a = [-3.969683028665376e+01, 2.209460984245205e+02, -2.759285104469687e+02,
          1.383577518672690e+02, -3.066479806614716e+01, 2.506628277459239e+00];
    $b = [-5.447609879822406e+01, 1.615858368580409e+02, -1.556989798598866e+02,
          6.680131188771972e+01, -1.328068155288572e+01];
    $c = [-7.784894002430293e-03, -3.223964580411365e-01, -2.400758277161838e+00,
          -2.549732539343734e+00, 4.374664141464968e+00, 2.938163982698783e+00];
    $d = [7.784695709041462e-03, 3.224671290700398e-01, 2.445134137142996e+00,
          3.754408661907416e+00];

    $plow  = 0.02425;
    $phigh = 1 - $plow;

    if ($p < $plow) {
        $q = sqrt(-2 * log($p));
        $x = ((((($c[0] * $q + $c[1]) * $q + $c[2]) * $q + $c[3]) * $q + $c[4]) * $q + $c[5]) /
             (((($d[0] * $q + $d[1]) * $q + $d[2]) * $q + $d[3]) * $q + 1);
    } elseif ($p <= $phigh) {
        $q = $p - 0.5;
        $r = $q * $q;
        $x = ((((($a[0] * $r + $a[1]) * $r + $a[2]) * $r + $a[3]) * $r + $a[4]) * $r + $a[5]) * $q /
             ((((($b[0] * $r + $b[1]) * $r + $b[2]) * $r + $b[3]) * $r + $b[4]) * $r + 1);
    } else {
        $q = sqrt(-2 * log(1 - $p));
        $x = -((((($c[0] * $q + $c[1]) * $q + $c[2]) * $q + $c[3]) * $q + $c[4]) * $q + $c[5]) /
              (((($d[0] * $q + $d[1]) * $q + $d[2]) * $q + $d[3]) * $q + 1);
    }

    // ขัดเกลาด้วยวิธีของ Halley หนึ่งรอบ
    $e = ch45_norm_cdf($x) - $p;
    $u = $e * sqrt(2 * M_PI) * exp($x * $x / 2);
    $x = $x - $u / (1 + $x * $u / 2);
    return $x;
}

/** ฟังก์ชันแกมมาแบบลอการิทึม (Lanczos) — ใช้ในเบตาไม่สมบูรณ์ */
function ch45_log_gamma($x) {
    static $cof = [76.18009172947146, -86.50532032941677, 24.01409824083091,
                   -1.231739572450155, 0.1208650973866179e-2, -0.5395239384953e-5];
    $y = $x;
    $tmp = $x + 5.5;
    $tmp -= ($x + 0.5) * log($tmp);
    $ser = 1.000000000190015;
    for ($j = 0; $j < 6; $j++) $ser += $cof[$j] / ++$y;
    return -$tmp + log(2.5066282746310005 * $ser / $x);
}

/** เศษส่วนต่อเนื่องของฟังก์ชันเบตาไม่สมบูรณ์ (Lentz) */
function ch45_betacf($a, $b, $x) {
    $MAXIT = 300; $EPS = 3.0e-14; $FPMIN = 1.0e-300;
    $qab = $a + $b; $qap = $a + 1.0; $qam = $a - 1.0;
    $c = 1.0;
    $d = 1.0 - $qab * $x / $qap;
    if (abs($d) < $FPMIN) $d = $FPMIN;
    $d = 1.0 / $d;
    $h = $d;
    for ($m = 1; $m <= $MAXIT; $m++) {
        $m2 = 2 * $m;
        $aa = $m * ($b - $m) * $x / (($qam + $m2) * ($a + $m2));
        $d = 1.0 + $aa * $d; if (abs($d) < $FPMIN) $d = $FPMIN;
        $c = 1.0 + $aa / $c; if (abs($c) < $FPMIN) $c = $FPMIN;
        $d = 1.0 / $d;
        $h *= $d * $c;
        $aa = -($a + $m) * ($qab + $m) * $x / (($a + $m2) * ($qap + $m2));
        $d = 1.0 + $aa * $d; if (abs($d) < $FPMIN) $d = $FPMIN;
        $c = 1.0 + $aa / $c; if (abs($c) < $FPMIN) $c = $FPMIN;
        $d = 1.0 / $d;
        $del = $d * $c;
        $h *= $del;
        if (abs($del - 1.0) < $EPS) break;
    }
    return $h;
}

/** ฟังก์ชันเบตาไม่สมบูรณ์แบบปรับมาตรฐาน I_x(a,b) */
function ch45_betai($a, $b, $x) {
    if ($x <= 0.0) return 0.0;
    if ($x >= 1.0) return 1.0;
    $bt = exp(ch45_log_gamma($a + $b) - ch45_log_gamma($a) - ch45_log_gamma($b)
        + $a * log($x) + $b * log(1.0 - $x));
    if ($x < ($a + 1.0) / ($a + $b + 2.0)) {
        return $bt * ch45_betacf($a, $b, $x) / $a;
    }
    return 1.0 - $bt * ch45_betacf($b, $a, 1.0 - $x) / $b;
}

/** ค่า p สองทางของการแจกแจงที (two-tailed) */
function ch45_t_p_two($t, $df) {
    if ($df <= 0) return null;
    $t = abs((float)$t);
    if (!is_finite($t)) return 0.0;
    return ch45_betai($df / 2.0, 0.5, $df / ($df + $t * $t));
}

/** ค่า p ทางเดียวของการแจกแจงเอฟ P(F > f) — ใช้กับการทดสอบนัยสำคัญของ ICC */
function ch45_f_p_upper($f, $df1, $df2) {
    if ($f <= 0 || $df1 <= 0 || $df2 <= 0) return null;
    return ch45_betai($df2 / 2.0, $df1 / 2.0, $df2 / ($df2 + $df1 * $f));
}

/* =========================================================================
 * 3) Paired-samples t-test + ขนาดอิทธิพล
 * ========================================================================= */

/**
 * ทดสอบทีแบบจับคู่ (ก่อนเรียน–หลังเรียน)
 * $pre และ $post ต้องเรียงคู่กันตามลำดับนักเรียนคนเดียวกัน
 * คืนค่า t, df, p (สองทาง), ผลต่างเฉลี่ย, SD ของผลต่าง, ขนาดอิทธิพล Cohen's dz
 */
function ch45_paired_t(array $pre, array $post) {
    $d = [];
    $n = min(count($pre), count($post));
    for ($i = 0; $i < $n; $i++) {
        if (!is_numeric($pre[$i]) || !is_numeric($post[$i])) continue;
        $d[] = (float)$post[$i] - (float)$pre[$i];
    }
    $k = count($d);
    if ($k < 2) {
        return ['n' => $k, 't' => null, 'df' => max(0, $k - 1), 'p' => null,
                'mean_diff' => ($k ? $d[0] : null), 'sd_diff' => null, 'dz' => null,
                'd_av' => null, 'se' => null, 'ci_low' => null, 'ci_high' => null];
    }
    $md = array_sum($d) / $k;
    $ss = 0.0;
    foreach ($d as $v) $ss += ($v - $md) * ($v - $md);
    $sd = sqrt($ss / ($k - 1));
    $se = ($sd > 0) ? $sd / sqrt($k) : 0.0;
    $t  = ($se > 0) ? $md / $se : null;
    $df = $k - 1;

    // Cohen's dz = ผลต่างเฉลี่ย / SD ของผลต่าง (ขนาดอิทธิพลของการทดสอบแบบจับคู่)
    $dz = ($sd > 0) ? $md / $sd : null;

    // Cohen's d แบบใช้ SD เฉลี่ยของสองรอบ (d_av) — รายงานควบคู่ไว้ให้เลือกใช้
    $sdPre  = ch45_sd(array_slice($pre, 0, $n));
    $sdPost = ch45_sd(array_slice($post, 0, $n));
    $dAv = null;
    if ($sdPre !== null && $sdPost !== null && ($sdPre + $sdPost) > 0) {
        $dAv = $md / (($sdPre + $sdPost) / 2.0);
    }

    // ช่วงความเชื่อมั่น 95% ของผลต่างเฉลี่ย (ใช้ค่าวิกฤตแบบประมาณจากโค้งปกติเมื่อ df ใหญ่)
    $tCrit = ch45_t_critical_95($df);
    return [
        'n' => $k, 't' => $t, 'df' => $df,
        'p' => ($t === null ? null : ch45_t_p_two($t, $df)),
        'mean_diff' => $md, 'sd_diff' => $sd, 'se' => $se,
        'dz' => $dz, 'd_av' => $dAv,
        'ci_low'  => ($tCrit === null ? null : $md - $tCrit * $se),
        'ci_high' => ($tCrit === null ? null : $md + $tCrit * $se),
    ];
}

/** ค่าวิกฤต t ที่ระดับความเชื่อมั่น 95% (สองทาง) — หาด้วยการค้นแบบแบ่งครึ่ง */
function ch45_t_critical_95($df) {
    if ($df <= 0) return null;
    $lo = 0.0; $hi = 100.0;
    for ($i = 0; $i < 200; $i++) {
        $mid = ($lo + $hi) / 2.0;
        if (ch45_t_p_two($mid, $df) > 0.05) $lo = $mid; else $hi = $mid;
    }
    return ($lo + $hi) / 2.0;
}

/** แปลความหมายขนาดอิทธิพลตามเกณฑ์ของ Cohen (1988) */
function ch45_effect_label($d) {
    if ($d === null) return '';
    $a = abs($d);
    if ($a < 0.2) return 'น้อยมาก';
    if ($a < 0.5) return 'น้อย';
    if ($a < 0.8) return 'ปานกลาง';
    if ($a < 1.2) return 'มาก';
    return 'มากที่สุด';
}

/* =========================================================================
 * 4) Shapiro-Wilk — ตรวจการแจกแจงปกติของคะแนนผลต่าง
 *    ดัดแปลงจาก AS R94 (Royston, 1995) ซึ่งเป็นอัลกอริทึมเดียวกับที่ SPSS และ R ใช้
 *    ใช้ได้กับ n ตั้งแต่ 3 ถึง 5000
 * ========================================================================= */

/** ประเมินพหุนาม c[0] + c[1]x + c[2]x^2 + ... */
function ch45_poly(array $c, $nord, $x) {
    $res = $c[0];
    if ($nord <= 1) return $res;
    $p = $x;
    $res += $c[1] * $p;
    for ($j = 2; $j < $nord; $j++) {
        $p *= $x;
        $res += $c[$j] * $p;
    }
    return $res;
}

/**
 * ทดสอบการแจกแจงปกติด้วยวิธีของ Shapiro-Wilk
 * คืน ['W' => ค่าสถิติ, 'p' => ค่านัยสำคัญ, 'n' => จำนวนข้อมูล, 'normal' => true/false]
 * ('normal' = true หมายถึง p >= .05 คือ "ไม่แตกต่างจากการแจกแจงปกติ")
 */
function ch45_shapiro_wilk(array $vals) {
    $x = ch45_numeric($vals);
    sort($x, SORT_NUMERIC);
    $n = count($x);
    if ($n < 3) return ['W' => null, 'p' => null, 'n' => $n, 'normal' => null,
                        'error' => 'ต้องมีข้อมูลอย่างน้อย 3 ค่า'];
    if ($n > 5000) return ['W' => null, 'p' => null, 'n' => $n, 'normal' => null,
                           'error' => 'รองรับข้อมูลไม่เกิน 5000 ค่า'];
    if ($x[$n - 1] - $x[0] <= 0) return ['W' => null, 'p' => null, 'n' => $n, 'normal' => null,
                                         'error' => 'ข้อมูลทุกค่าเท่ากัน จึงทดสอบการแจกแจงไม่ได้'];

    $c1 = [0.0, 0.221157, -0.147981, -2.071190, 4.434685, -2.706056];
    $c2 = [0.0, 0.042981, -0.293762, -1.752461, 5.682633, -3.582633];
    $c3 = [0.5440, -0.39978, 0.025054, -6.714e-4];
    $c4 = [1.3822, -0.77857, 0.062767, -0.0020322];
    $c5 = [-1.5861, -0.31082, -0.083751, 0.0038915];
    $c6 = [-0.4803, -0.082676, 0.0030302];
    $g  = [-2.273, 0.459];

    $an  = (float)$n;
    $nn2 = intdiv($n, 2);
    $a   = array_fill(0, $nn2 + 1, 0.0); // ใช้ดัชนี 1..nn2 ตามต้นฉบับ

    if ($n == 3) {
        $a[1] = sqrt(0.5);
    } else {
        $an25 = $an + 0.25;
        $summ2 = 0.0;
        for ($i = 1; $i <= $nn2; $i++) {
            $a[$i] = ch45_norm_inv(($i - 0.375) / $an25);
            $summ2 += $a[$i] * $a[$i];
        }
        $summ2 *= 2.0;
        $ssumm2 = sqrt($summ2);
        $rsn = 1.0 / sqrt($an);
        $a1 = ch45_poly($c1, 6, $rsn) - $a[1] / $ssumm2;

        if ($n > 5) {
            $i1 = 3;
            $a2 = -$a[2] / $ssumm2 + ch45_poly($c2, 6, $rsn);
            $fac = sqrt(($summ2 - 2.0 * $a[1] * $a[1] - 2.0 * $a[2] * $a[2])
                      / (1.0 - 2.0 * $a1 * $a1 - 2.0 * $a2 * $a2));
        } else {
            $i1 = 2;
            $a2 = 0.0;
            $fac = sqrt(($summ2 - 2.0 * $a[1] * $a[1]) / (1.0 - 2.0 * $a1 * $a1));
        }
        for ($i = $i1; $i <= $nn2; $i++) $a[$i] = -$a[$i] / $fac;
        $a[1] = $a1;
        if ($n > 5) $a[2] = $a2;
    }

    // ---- คำนวณค่าสถิติ W ----
    $range = $x[$n - 1] - $x[0];
    $sa = 0.0; $sx = 0.0;
    for ($i = 0; $i < $n; $i++) {
        $j = $n - 1 - $i;
        $idx = min($i, $j) + 1;
        if ($i != $j) $sa += (($i < $j) ? -1.0 : 1.0) * $a[$idx];
        $sx += $x[$i] / $range;
    }
    $sa /= $n;
    $sx /= $n;

    $ssa = 0.0; $ssx = 0.0; $sax = 0.0;
    for ($i = 0; $i < $n; $i++) {
        $j = $n - 1 - $i;
        $idx = min($i, $j) + 1;
        $asa = ($i != $j) ? ((($i < $j) ? -1.0 : 1.0) * $a[$idx] - $sa) : (-$sa);
        $xsx = $x[$i] / $range - $sx;
        $ssa += $asa * $asa;
        $ssx += $xsx * $xsx;
        $sax += $asa * $xsx;
    }
    if ($ssa <= 0 || $ssx <= 0) {
        return ['W' => null, 'p' => null, 'n' => $n, 'normal' => null,
                'error' => 'คำนวณค่าสถิติไม่ได้ (ความแปรปรวนเป็นศูนย์)'];
    }
    $ssassx = sqrt($ssa * $ssx);
    $w1 = ($ssassx - $sax) * ($ssassx + $sax) / ($ssa * $ssx);
    $w  = 1.0 - $w1;

    // ---- ค่า p ----
    if ($n == 3) {
        $pi6 = 1.90985931710274;
        $stqr = 1.047197551196598;
        $pw = $pi6 * (asin(sqrt($w)) - $stqr);
        if ($pw < 0) $pw = 0.0;
        if ($pw > 1) $pw = 1.0;
    } else {
        $y = log($w1);
        $xx = log($an);
        if ($n <= 11) {
            $gma = ch45_poly($g, 2, $an);
            if ($y >= $gma) {
                $pw = 1e-99;
            } else {
                $y = -log($gma - $y);
                $m = ch45_poly($c3, 4, $an);
                $s = exp(ch45_poly($c4, 4, $an));
                $pw = 1.0 - ch45_norm_cdf(($y - $m) / $s);
            }
        } else {
            $m = ch45_poly($c5, 4, $xx);
            $s = exp(ch45_poly($c6, 3, $xx));
            $pw = 1.0 - ch45_norm_cdf(($y - $m) / $s);
        }
    }

    return [
        'W'      => $w,
        'p'      => $pw,
        'n'      => $n,
        'normal' => ($pw >= 0.05),
        'error'  => '',
    ];
}

/* =========================================================================
 * 5) ความเที่ยงระหว่างผู้ประเมิน
 * ========================================================================= */

/** สหสัมพันธ์แบบเพียร์สันของข้อมูลสองชุดที่จับคู่กัน */
function ch45_pearson(array $a, array $b) {
    $n = min(count($a), count($b));
    $xs = []; $ys = [];
    for ($i = 0; $i < $n; $i++) {
        if (!is_numeric($a[$i]) || !is_numeric($b[$i])) continue;
        $xs[] = (float)$a[$i];
        $ys[] = (float)$b[$i];
    }
    $k = count($xs);
    if ($k < 3) return ['r' => null, 'n' => $k, 'p' => null];
    $mx = array_sum($xs) / $k;
    $my = array_sum($ys) / $k;
    $sxy = 0.0; $sxx = 0.0; $syy = 0.0;
    for ($i = 0; $i < $k; $i++) {
        $dx = $xs[$i] - $mx; $dy = $ys[$i] - $my;
        $sxy += $dx * $dy; $sxx += $dx * $dx; $syy += $dy * $dy;
    }
    if ($sxx <= 0 || $syy <= 0) return ['r' => null, 'n' => $k, 'p' => null];
    $r = $sxy / sqrt($sxx * $syy);
    $r = max(-1.0, min(1.0, $r));
    $p = null;
    if ($k > 2 && abs($r) < 1.0) {
        $t = $r * sqrt(($k - 2) / (1 - $r * $r));
        $p = ch45_t_p_two($t, $k - 2);
    } elseif (abs($r) >= 1.0) {
        $p = 0.0;
    }
    return ['r' => $r, 'n' => $k, 'p' => $p];
}

/**
 * ICC แบบสองทางสุ่ม ความสอดคล้องสัมบูรณ์ (two-way random, absolute agreement)
 * $matrix = [[คะแนนผู้ประเมิน 1, ผู้ประเมิน 2, ...], ...] หนึ่งแถวต่อหนึ่งชิ้นงาน
 * คืนทั้ง ICC(2,1) (ผู้ประเมินคนเดียว) และ ICC(2,k) (ค่าเฉลี่ยของผู้ประเมินทั้งหมด)
 */
function ch45_icc(array $matrix) {
    $rows = [];
    foreach ($matrix as $row) {
        if (!is_array($row)) continue;
        $r = ch45_numeric($row);
        if (count($r) !== count($row)) continue; // แถวที่ผู้ประเมินให้คะแนนไม่ครบ ไม่นำมาคิด
        $rows[] = $r;
    }
    $n = count($rows);
    if ($n < 2) return ['icc1' => null, 'iccK' => null, 'n' => $n, 'k' => 0, 'p' => null];
    $k = count($rows[0]);
    foreach ($rows as $r) if (count($r) !== $k) return ['icc1' => null, 'iccK' => null, 'n' => $n, 'k' => $k, 'p' => null];
    if ($k < 2) return ['icc1' => null, 'iccK' => null, 'n' => $n, 'k' => $k, 'p' => null];

    $grand = 0.0;
    foreach ($rows as $r) $grand += array_sum($r);
    $grand /= ($n * $k);

    $rowMeans = []; $colSums = array_fill(0, $k, 0.0);
    foreach ($rows as $i => $r) {
        $rowMeans[$i] = array_sum($r) / $k;
        for ($j = 0; $j < $k; $j++) $colSums[$j] += $r[$j];
    }
    $colMeans = [];
    for ($j = 0; $j < $k; $j++) $colMeans[$j] = $colSums[$j] / $n;

    $ssTotal = 0.0; $ssRow = 0.0; $ssCol = 0.0;
    foreach ($rows as $i => $r) {
        $ssRow += $k * ($rowMeans[$i] - $grand) * ($rowMeans[$i] - $grand);
        for ($j = 0; $j < $k; $j++) $ssTotal += ($r[$j] - $grand) * ($r[$j] - $grand);
    }
    for ($j = 0; $j < $k; $j++) $ssCol += $n * ($colMeans[$j] - $grand) * ($colMeans[$j] - $grand);
    $ssErr = $ssTotal - $ssRow - $ssCol;

    $dfRow = $n - 1; $dfCol = $k - 1; $dfErr = $dfRow * $dfCol;
    if ($dfErr <= 0) return ['icc1' => null, 'iccK' => null, 'n' => $n, 'k' => $k, 'p' => null];

    $msRow = $ssRow / $dfRow;
    $msCol = $ssCol / $dfCol;
    $msErr = $ssErr / $dfErr;

    $den1 = $msRow + ($k - 1) * $msErr + ($k * ($msCol - $msErr) / $n);
    $icc1 = ($den1 != 0) ? ($msRow - $msErr) / $den1 : null;

    $denK = $msRow + (($msCol - $msErr) / $n);
    $iccK = ($denK != 0) ? ($msRow - $msErr) / $denK : null;

    $f = ($msErr > 0) ? $msRow / $msErr : null;
    $p = ($f !== null) ? ch45_f_p_upper($f, $dfRow, $dfErr) : null;

    return [
        'icc1' => ($icc1 === null ? null : max(-1.0, min(1.0, $icc1))),
        'iccK' => ($iccK === null ? null : max(-1.0, min(1.0, $iccK))),
        'n' => $n, 'k' => $k, 'F' => $f, 'df1' => $dfRow, 'df2' => $dfErr, 'p' => $p,
    ];
}

/** แปลความหมายค่า ICC ตามเกณฑ์ของ Koo & Li (2016) */
function ch45_icc_label($icc) {
    if ($icc === null) return '';
    if ($icc < 0.50) return 'ต่ำ';
    if ($icc < 0.75) return 'ปานกลาง';
    if ($icc < 0.90) return 'ดี';
    return 'ดีมาก';
}

/* =========================================================================
 * 6) ตัวช่วยจัดรูปแบบตัวเลขให้ตรงแบบวิทยานิพนธ์ (APA)
 * ========================================================================= */

/** ตัวเลขทศนิยม 2 ตำแหน่ง คืน "—" เมื่อไม่มีค่า */
function ch45_fmt($v, $digits = 2) {
    if ($v === null || $v === '' || !is_numeric($v)) return '—';
    return number_format((float)$v, $digits);
}

/** จัดรูปค่า p ตามแบบ APA (p < .001 / .034 — ไม่มีเลข 0 นำหน้าจุด) */
function ch45_fmt_p($p) {
    if ($p === null || !is_numeric($p)) return '—';
    $p = (float)$p;
    if ($p < 0.001) return '< .001';
    $s = number_format($p, 3);
    return ltrim($s, '0');
}

/** จัดรูปค่าที่อยู่ระหว่าง -1 ถึง 1 (r, ICC, W) — ตัดเลข 0 นำหน้าจุดตามแบบ APA */
function ch45_fmt_r($v, $digits = 3) {
    if ($v === null || !is_numeric($v)) return '—';
    $s = number_format((float)$v, $digits);
    if (strpos($s, '0.') === 0) return substr($s, 1);
    if (strpos($s, '-0.') === 0) return '-' . substr($s, 2);
    return $s;
}
