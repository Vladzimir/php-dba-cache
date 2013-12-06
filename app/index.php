<?php
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'bootstrap.php';

// produce us a cache and sweeper object please
function factory($config)
{
  extract($config);
  $cache = new Cache($file, $handler, $mode, $persistently);
  $sweep = new Sweep($cache);

  return array($cache, $sweep);
}

// create an test-entry for two seconds.
function put(Cache $cache)
{
  return $cache->put(uniqid('test_'), (object)array( rand(1, 100) ), 2);
}

// pretty printer for byte values.
function bsize($s) {
	foreach (array('','K','M','G') as $k) {
		if ($s < 1024) break;
		$s /= 1024;
	}
	return sprintf("%5.1f %sBytes",$s,$k);
}

function flash_msg(&$check, $msg)
{
  if (isset($check) && $check === true) {
    echo '<div class="alert alert-success">' . $msg . '</div>';
  } elseif (isset($check) && $check === false) {
    echo '<div class="alert alert-error"><strong>NO!</strong> ' . $msg . '</div>';
  }
}

// retrieve an cache and a cache-sweeper.
try {
  list($cache, $sweep) = factory($config);
} catch (Exception $e) {
  die($e->getMessage());
}

// load the configuration at the global namespace.
extract($config);

// make a list of all the available handlers.
$available_handlers = '';
foreach (dba_handlers(true) as $handler_name => $handler_version) {
  $handler_version = str_replace('$', '', $handler_version);
  if($handler == $handler_name) {
    $handler_in_use  = "$handler_name: $handler_version <br />";
    continue;
  }
  $available_handlers .= "$handler_name: $handler_version <br />";
}

// compute the user authentication.
$authenticated = false;
if (isset($_POST['login']) || isset($_SERVER['PHP_AUTH_USER'])) {
	if (!isset($_SERVER['PHP_AUTH_USER']) ||
		!isset($_SERVER['PHP_AUTH_PW']) ||
		$_SERVER['PHP_AUTH_USER'] != $authentication['username'] ||
		$_SERVER['PHP_AUTH_PW'] != $authentication['password']) {
		Header("WWW-Authenticate: Basic realm=\"PHP DBA Cache Login\"");
		Header("HTTP/1.0 401 Unauthorized");
    exit;
	}
    $authenticated = true;
}


if(isset($_POST['create-test-entry'])) {
  $create_test_entry = put($cache);
}

if(isset($_POST['optimize'])) {
  $optimize = @dba_optimize($cache->getDba());
}

if(isset($_POST['synchronize'])) {
  $synchronize = @dba_sync($cache->getDba());
}

if($authenticated && isset($_POST['delete-old'])) {
  $delete_old = true;
  $sweep->old();
}

if ($authenticated && isset($_POST['delete-all'])) {

  try{
    $delete_all = $sweep->flush();
  } catch (RuntimeException $re) {
    $delete_all = false;
  }

  list($cache, $sweep) = factory($config);
  put($cache);
}

// find the host-name
$host = php_uname('n');
if ($host) {
  $host = '(' . $host . ')';
}
if (isset($_SERVER['SERVER_ADDR'])) {
  $host .= ' (' . $_SERVER['SERVER_ADDR'] . ')';
}

clearstatcache();
$file_info  = new SplFileInfo($file);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>PHP DBA Cache Monitor by Gjero Krsteski</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="PHP DBA Cache Monitor">
    <meta name="author" content="Gjero Krsteski">
    <link href="bootstrap.min.css" rel="stylesheet" type="text/css"/>
    <style>
        body {
            padding-top : 60px; /* 60px to make the container go all the way to the bottom of the topbar */
        }
        .memory-usage {
            min-height     : 32px;
            max-height     : 42px;
            text-align     : center;
            vertical-align : middle;
        }
    </style>
    <link href="//netdna.bootstrapcdn.com/twitter-bootstrap/2.3.0/css/bootstrap-responsive.min.css" rel="stylesheet">
    <!-- HTML5 shim, for IE6-8 support of HTML5 elements -->
    <!--[if lt IE 9]>
  <script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>
    <![endif]-->
    <!-- Fav and touch icons -->
    <link rel="shortcut icon" href="favicon.ico">
</head>

<body>
<div class="navbar navbar-inverse navbar-fixed-top">
    <div class="navbar-inner">
        <div class="container">
            <a class="btn btn-navbar collapsed" data-toggle="collapse" data-target=".nav-collapse"> <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>

            </a>
            <a class="brand" style="color:white;" href="#">DBA Cache Monitor</a>

            <div class="nav-collapse collapse" style="height: 0px;">
                <ul class="nav">
                    <li>
                        <a href="#general">General</a>
                    </li>
                    <li>
                        <a href="#memory">Memory Usage</a>
                    </li>
                    <li>
                        <a href="#speed">Tune Cache</a>
                    </li>
                    <li>
                        <a href="#entries">Cache Entries</a>
                    </li>
                </ul>
            </div>
            <!--/.nav-collapse -->
        </div>
    </div>
</div>
<div class="container">

    <p class="">The php-dba-cache uses the database (dbm-style) abstraction layer to store your PHP data. It depends on the free space of your disk.</p>

    <section>
        <a class="anchor" id="general"></a>

        <h3>General Information</h3>

        <table class="table table-bordered  table-striped  table-condensed">
            <tbody>
            <tr>
                <td class="">DBA Handler In Use</td>
                <td><?php echo $handler_in_use?></td>
            </tr>
            <tr>
                <td class="">Available DBA Handlers</td>
                <td><?php echo $available_handlers?></td>
            </tr>
                        <tr>
                <td class="">DBA Cache File</td>
                <td><?php echo $cache->getStorage()?></td>
            </tr>
            <tr>
                <td class="">PHP Version</td>
                <td><?php echo phpversion()?></td>
            </tr>

            <?php if (!empty($_SERVER['SERVER_NAME'])) : ?>
            <tr>
                <td class="">DBA Host</td>
                <td><?php echo $_SERVER['SERVER_NAME'] . ' ' . $host?></td>
            </tr>
              <?php endif; ?>

            <?php if (!empty($_SERVER['SERVER_SOFTWARE'])) : ?>
            <tr>
                <td class="">Server Software</td>
                <td><?php echo $_SERVER['SERVER_SOFTWARE']?></td>
            </tr>
              <?php endif; ?>
            <tr>
                <td class="">Start Time</td>
                <td><?php echo date($date_format, $file_info->getCTime())?></td>
            </tr>
            <tr>
                <td class="">Last Modified Time</td>
                <td><?php echo date($date_format, $file_info->getMTime())?></td>
            </tr>
            </tbody>
        </table>
    </section>

    <section>
        <a class="anchor" id="memory"></a>

        <h3>Memory Usage</h3>

        <div class="row">
            <div class="span4">
              <div class="well well-small">
                <h4><?php echo bsize($file_info->getSize())?></h4>
                <p>Cache file in use size</p>
              </div>
            </div>
            <div class="span4">
              <div class="well well-small">
                <h4><?php echo bsize(disk_free_space($file_info->getPath()))?></h4>
                <p>Cache directory free size</p>
              </div>
            </div>
            <div class="span4">
                   <div class="well well-small">
                     <h4><?php echo bsize(disk_total_space($file_info->getPath()))?></h4>
                     <p>Cache directory total size</p>
                   </div>
                 </div>
          </div>

    </section>

    <section>
        <a class="anchor" id="speed"></a>

        <h3>Tune Cache</h3>

       <?php flash_msg($synchronize, 'Database synchronized'); flash_msg($optimize, 'Database optimized'); ?>


        <p class="">If you add-remove-substitute keys with data having different content length,
            the db continues to grow, wasting space. Sometimes it is necessary, to optimize or synchronize the db in
            order to remove unused data from the db itself.
        </p>


        <a class="anchor" id="memory"></a>

        <form accept-charset="utf-8" method="post" class="well" action="#speed" name="memory-acts">

            <table class="table">
                <tbody>
                <tr>
                    <td class="" style="border-top:0;">
                        <button class="btn btn-success" type="submit" name="optimize">Optimize Cache</button>
                    </td>
                    <td style="border-top:0;">
                        <p class="">Optimize a database, which usually consists of eliminating gaps between records created by deletes.</p>
                    </td>
                </tr>

                <tr>
                    <td class="" style="border-top:0;">
                        <button class="btn btn-success" type="submit" name="synchronize">Synchronize Cache</button>
                    </td>
                    <td style="border-top:0;"><p class="">Synchronize the view of the database in memory and its image on the disk.
                        As you insert records, they may be cached in memory by the underlying engine.
                        Other processes reading from the database will not see these new records until synchronization.</p>
                    </td>
                </tr>

                </tbody>
            </table>

        </form>

    </section>

    <section>
        <a class="anchor" id="entries"></a>

        <h3>Cache Entries</h3>

        <?php
          flash_msg($create_test_entry, 'Entry created');
          flash_msg($delete_all, 'Cache flushed');
          flash_msg($delete_old, 'All old entries deleted');
        ?>

        <form accept-charset="utf-8" method="post" class="well" action="#entries" name="entries-acts">

            <button class="btn btn-success" type="submit" name="create-test-entry">Create Test Entry</button>

          <? if ($authenticated === false) : ?>
            <button class="btn btn-info" type="submit" name="login">Login and Sweep</button>
          <? endif; ?>

          <? if ($authenticated === true && $cache->erasable()) : ?>
            <button class="btn btn-success" type="submit" name="delete-old">Remove old entries</button>
          <? endif; ?>

          <? if ($authenticated === true) : ?>
            <button class="btn btn-danger" type="submit" name="delete-all">Flush Cache</button>
          <? endif; ?>
        </form>

        <div style="height: 276px;overflow: auto;margin-bottom: 22px">

            <table class="table table-bordered  table-striped  table-condensed ">
                <tbody>
                <tr>
                    <th class="">Key</th>
                    <th>Last Modification Time</th>
                    <th>Expire Time</th>
                </tr>
                <?php
                $key = dba_firstkey($cache->getDba());
                while ($key !== false && $key !== null) :
                  /* @var $item Capsule */
                  $item = $cache->fetch($key);

                  if ($item instanceof Capsule) :
                    ?>
                  <tr>
                      <td class=""><?php echo $key?></td>
                      <td><?php echo date($date_format, $item->mtime)?></td>
                      <td><?php echo date($date_format, ($item->mtime + $item->ltime))?></td>
                  </tr>
                    <?php
                  endif;
                  $key = dba_nextkey($cache->getDba());
                endwhile;
                ?>
                </tbody>
            </table>

        </div>


    </section>

    <p class="muted">
        Having trouble with PHP DBA Cache Monitor? Please contact <a href="mailto:gjero@krsteski.de">gjero@krsteski.de</a> and we’ll help you to sort it out.
        Because this GUI development and source control is done through GitHub, anyone is able to make contributions to it.
        Anyone can fix bugs and add features. <a href="https://github.com/gjerokrsteski/php-dba-cache" target="_blank">Fork it here!</a>
    </p>

</div>
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.8.2/jquery.min.js"></script>
<script src="//netdna.bootstrapcdn.com/twitter-bootstrap/2.3.0/js/bootstrap.min.js"></script>
</body>
</html>
