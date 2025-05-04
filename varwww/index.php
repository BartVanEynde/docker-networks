<?php

// Functie om gegevens op te halen van de Docker API
function get_docker_data($url) {
   $ch = curl_init($url);
   curl_setopt($ch, CURLOPT_UNIX_SOCKET_PATH, "/var/run/docker.sock");
   curl_setopt($ch, CURLOPT_URL, $url);
   curl_setopt($ch, CURLOPT_HTTPHEADER, array( 'Content-Type: application/json' ));   
   curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
   $response = curl_exec($ch);
   curl_close($ch);
   
   return json_decode($response, true);
}

function calculate_total_ips($subnet) {
   $subnet_parts = explode('/', $subnet);
   $cidr = $subnet_parts[1];
   $total_ips = pow(2, 32 - $cidr);
   return $total_ips;
}



// $networks = get_docker_data('http://localhost/v1.40//networks/json');
$networks = get_docker_data('http://localhost/v1.45/networks/');
$details = array();
foreach( $networks as $k=>$n ) {
	$details[$n['Name']] = get_docker_data('http://localhost/v1.45/networks/' . $n['Name']);
}
$networks = array(); // free some mem...

foreach ($details as $k=>$d) {
    $network_name = $d['Name'];
    // if( in_array( $network_name, array('bridge','null','host','overlay','ipvlan','macvlan') ) ) continue

    if( isset( $d['IPAM']['Config'][0]['Subnet'] ) ) {
        $details[$k]['Subnet'] = $d['IPAM']['Config'][0]['Subnet'];
    } else {
        $details[$k]['Subnet']  = '??';
    }

    $details[$k]['used_ips'] = count( $d['Containers'] );
    if( isset( $d['IPAM']['Config'][0]['Gateway'] ) ) {
        $details[$k]['used_ips']++;
    }
    $details[$k]['total_ips'] = calculate_total_ips($details[$k]['Subnet']);
    $details[$k]['available_ips'] = $details[$k]['total_ips'] - $details[$k]['used_ips'] - 2;
}

$images = get_docker_data( 'http://localhost/v1.45/images/json' ); 
$containers = get_docker_data( 'http://localhost/v1.45/containers/json?all=1' ); 




?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Docker Networks</title>
    <link id="favicon" rel="shortcut icon" type="image/png" href="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAYAAABzenr0AAAACXBIWXMAAAsTAAALEwEAmpwYAAABM0lEQVR4nO3UPUvDYBTF8R8uvuAkCKJDcVMRtOAijq5WcOiX0C/h6CboJn4AcXHV3UUQXBWlCC4KIr538JVACqWQNm2TdGj/cOG5IXBO7rl56NGjdQr4Cys4Z06xykBwzpw8DsOa1w0ZFxr0qWdcbNBHMYrZJDLON+ij2MNHp/6UYXyGU3pFLsnMCzF2YrHqWVCbSWZejLETSzUGnnGNWxxjrZ3M4+xEEMF7jYnqOpciI1jFVYT4GxaCF/fxXcdl0lXGEWYqTssZCZcwhv7aUd1lZGA9KquzDMQfMBhlYLuTXx+wkrL4KfrUYRgvKYnfY0IMtlLKfS6OeGUKpQTFLzGlSabDkbUj/IVdDGmRcRzgp0nh4FrdwaSEyGEjNHOBx/DG/MUTbnAS7s4yBpIS7tF9/AOXlmL2R+jpvAAAAABJRU5ErkJggg==">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }

        /* Navigation Bar */
        nav {
            display: flex; 
            justify-content: space-between;
            align-items: center;
            background-color: #333;
            padding: 10px 15px;
            color: white;

            position: fixed;
            top: 0px;
            width: 100%;

            z-index: 1;
        }

        nav .title {
            font-size: 1.5em;
        }

        nav .title a {
            text-decoration: none;
            color: white;
        }

        nav .title a:hover {
            color: #CCC;
        }
        nav .menu {
            display: none;
        }

        nav .menu.active {
            display: block;
        }

        nav .menu li {
            list-style: none;
            margin: 10px 0;
        }

        nav .menu li a {
            color: white;
            text-decoration: none;
        }



        nav ul.icons {
            list-style-type: none;
            margin: 0;
            padding: 0;
            overflow: hidden;
            background-color: #333333;
        }

        nav ul.icons li {
            float: left;
        }

        nav ul.icons li a {
            display: block;
            color: white;
            text-align: center;
            padding: 0px 5px;
            text-decoration: none;
        }

        nav ul.icons li a:hover {
            background-color: #111111;
        }







        nav .search-container {
            display: flex;
            align-items: center;
            width: 40%;
            padding: 0 20px 0 0;
        }

        nav input[type="search"] {
            width: 100%;
            padding: 5px;
            margin-right: 5px;
            border: none;
            border-radius: 4px;
        }

        nav input[type="search"]:focus {
            outline: none;
        }

        .hamburger {
            display: none;
            cursor: pointer;
            font-size: 1.5em;
            padding: 0 30px 0 0;
        }

        h1 {
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
            padding: 2em 0 0 0;
        }

        h1 .back-to-top {
            position: absolute;
            right: 0;
            top: 50%;
            transform: translateY(-50%);
            text-decoration: none;
        }

         h1 .back-to-top svg {
            width: 24px;
            height: 24px;
            fill: #333;
            transition: fill 0.3s;
         }

        h1 .back-to-top:hover svg {
            fill: #0077cc;
        }
        /* Responsive design for mobile */
        @media (max-width: 768px) {
            .hamburger {
                display: block;
            }

            nav .search-container {
                width: auto;
            }

            nav .menu {
                position: absolute;
                top: 50px;
                right: 0;
                background-color: #333;
                padding: 20px;
                width: 200px;
                display: none;
                flex-direction: column;
            }

            nav .menu.active {
                display: block;
            }

            nav .menu li {
                text-align: center;
            }

            nav .menu li a {
                padding: 10px;
                display: block;
            }

        }

        /* Main content */
        .container {
            padding: 60px 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            table-layout: auto; /* Allow the table to expand and contract with the content */
        }

        th, td {
            padding: 10px;
            text-align: left;
            border: 1px solid #ddd;
            white-space: nowrap; /* Prevent text from wrapping */
            cursor: pointer; /* Make cells clickable */
        }

        th {
            background-color: #333;
            color: white;
            cursor: pointer;
        }

        th.sorted-asc:after {
            content: " ▲";
        }

        th.sorted-desc:after {
            content: " ▼";
        }

        tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        /* Scrollable table */
        .table-container {
            overflow-x: auto;
            margin-bottom: 20px;
        }

        .clear-btn {
            margin-right: 10px;
            cursor: pointer;
            font-size: 0.9em;
            padding: 5px;
            border-radius: 4px;
            border: 1px solid #044004;
            background-color: #40f040;
        }

    </style>
</head>
<body>
<a id="top"></a>
    <!-- Navigation Bar -->
    <nav>
        <div class="title"><a href="/">Docker Networks</a></div>
        <ul class="icons">
            <li id="networks-icon"><a href="#networks" aria-label="Networks"><img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='24' height='24' version='1'%3E%3Cpath fill='%23FFFFFF' d='M10,2C8.89,2 8,2.89 8,4V7C8,8.11 8.89,9 10,9H11V11H2V13H6V15H5C3.89,15 3,15.89 3,17V20C3,21.11 3.89,22 5,22H9C10.11,22 11,21.11 11,20V17C11,15.89 10.11,15 9,15H8V13H16V15H15C13.89,15 13,15.89 13,17V20C13,21.11 13.89,22 15,22H19C20.11,22 21,21.11 21,20V17C21,15.89 20.11,15 19,15H18V13H22V11H13V9H14C15.11,9 16,8.11 16,7V4C16,2.89 15.11,2 14,2H10M10,4H14V7H10V4M5,17H9V20H5V17M15,17H19V20H15V17Z'%3E%3C/path%3E%3C/svg%3E" /></a></li>
            <li id="ipam-icon"><a href="#ipam" aria-label="IP"><img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='24' height='24' version='1'%3E%3Cpath fill='%23FFFFFF' d='M15,9H13V7H15V9M22,20V22H15A1,1 0 0,1 14,23H10A1,1 0 0,1 9,22H2V20H9A1,1 0 0,1 10,19H11V17H7A2,2 0 0,1 5,15V5A2,2 0 0,1 7,3H17A2,2 0 0,1 19,5V15A2,2 0 0,1 17,17H13V19H14A1,1 0 0,1 15,20H22M9,5H7V15H9V5M11,15H13V11H15A2,2 0 0,0 17,9V7A2,2 0 0,0 15,5H11V15Z'%3E%3C/path%3E%3C/svg%3E" /></a></li>
            <li id="ports-icon"><a href="#ports" aria-label="Ports"><img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='24' height='24' version='1'%3E%3Cpath fill='%23FFFFFF' d='M9 6V11H7V7H5V11H3V9H1V21H3V19H5V21H7V19H9V21H11V19H13V21H15V19H17V21H19V19H21V21H23V9H21V11H19V7H17V11H15V6H13V11H11V6H9M3 13H5V17H3V13M7 13H9V17H7V13M11 13H13V17H11V13M15 13H17V17H15V13M19 13H21V17H19V13Z'%3E%3C/path%3E%3C/svg%3E" /></a></li>
            <li id="images-icon"><a href="#images" aria-label="Images"><img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='24' height='24' version='1'%3E%3Cpath fill='%23FFFFFF' d='M8.5,13.5L11,16.5L14.5,12L19,18H5M21,19V5C21,3.89 20.1,3 19,3H5A2,2 0 0,0 3,5V19A2,2 0 0,0 5,21H19A2,2 0 0,0 21,19Z'%3E%3C/path%3E%3C/svg%3E" /></a></li>
            <li id="containers-icon"><a href="#containers" aria-label="Containers"><img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='24' height='24' version='1'%3E%3Cpath fill='%23FFFFFF' d='M21,16.5C21,16.88 20.79,17.21 20.47,17.38L12.57,21.82C12.41,21.94 12.21,22 12,22C11.79,22 11.59,21.94 11.43,21.82L3.53,17.38C3.21,17.21 3,16.88 3,16.5V7.5C3,7.12 3.21,6.79 3.53,6.62L11.43,2.18C11.59,2.06 11.79,2 12,2C12.21,2 12.41,2.06 12.57,2.18L20.47,6.62C20.79,6.79 21,7.12 21,7.5V16.5M12,4.15L6.04,7.5L12,10.85L17.96,7.5L12,4.15M5,15.91L11,19.29V12.58L5,9.21V15.91M19,15.91V9.21L13,12.58V19.29L19,15.91Z'%3E%3C/path%3E%3C/svg%3E" /></a></li>

        </ul>
        <div class="search-container">
            <button class="clear-btn" id="clearSearchBtn" style="display:none;">Clear</button>
            <input type="search" id="searchBar" placeholder="Search...">
        </div>
        <div class="hamburger" id="hamburger">&#9776;</div>
        <ul class="menu" id="menu">
            <li><a href="#networks">Networks</a></li>
            <li><a href="#ipam">IPAM</a></li>
            <li><a href="#ports">Ports</a></li>
            <li><a href="#images">Images</a></li>
            <li><a href="#containers">Containers</a></li>
        </ul>
    </nav>

    <!-- Main Content -->
    <div class="container">

    <!-- ----------------------------------------------------------------------------------------------------------- -->
    <!-- Network table                                                                                               -->
    <!-- 1 line per network                                                                                          -->
    <!-- ----------------------------------------------------------------------------------------------------------- -->
        <h1 class="section-title" id="networks">
            Networks <nbsp> <sup id="networks-span">0</sup>
        </h1>
        <div class="table-container">
            <table id="networks-table">
                <thead>
                    <tr>
                        <th onclick="sortTable(0, 'networks-table', false)">Name</th>
                        <th onclick="sortTable(1, 'networks-table', false)">Driver</th>
                        <th onclick="sortTable(2, 'networks-table', false)">Defined in</th>
                        <th onclick="sortTable(3, 'networks-table', false)">Subnet</th>
                        <th onclick="sortTable(4, 'networks-table', true)">Total</th>
                        <th onclick="sortTable(5, 'networks-table', true)">Used</th>
                        <th onclick="sortTable(6, 'networks-table', true)">Available</th>
                    </tr>
                </thead>
                <tbody>
<?php
                foreach ($details as $d) {
                    echo "                    <tr>";
                    echo "<td>".( str_ends_with($d['Name'], '_default') ? "<span style='font-style: italic;' title='It seems you have no name defined...'>".$d['Name']."</span>" : $d['Name'] )."</td>";
                    echo "<td>{$d['Driver']}</td>";
                    echo "<td>".($d['Labels']['com.docker.compose.project']?:"<i>Default</i>")."</td>";
                    echo "<td>{$d['Subnet']}</td>";
                    echo "<td style='text-align: right;'>".($d['Labels']['com.docker.compose.project']? $d['total_ips'] :"??")."</td>";
                    echo "<td style='text-align: right;'>".($d['used_ips']<=2? "<span style='color: red;' title='I hope you have future plans with this...'>".$d['used_ips']."</span>" :$d['used_ips'])."</td>"; 
                    echo "<td style='text-align: right;'>".($d['Labels']['com.docker.compose.project']? $d['available_ips'] :"??")."</td>";
                    echo "</tr>\n";
                }
?>
                </tbody>
            </table>
        </div>



    <!-- ----------------------------------------------------------------------------------------------------------- -->
    <!-- IPAM - IP address management                                                                                -->
    <!-- 1 line per IP per container - a container with 2 IP's has here 2 lines...                                   -->
    <!-- ----------------------------------------------------------------------------------------------------------- -->
        <h1 class="section-title" id="ipam">
            IPAM <nbsp> <sup id="ipam-span">0</sup>
            <a href="#top" class="back-to-top" aria-label="Back to top">
                <svg viewBox="0 0 24 24">
                <path d="M12 4l-8 8h5v8h6v-8h5z"/>
                </svg>
            </a>
        </h1>
        <div class="table-container">
            <table id="ipam-table">
                <thead>
                    <tr>
                        <th onclick="sortTable(0, 'ipam-table', false)">Hosts</th>
                        <th onclick="sortTable(1, 'ipam-table', false)">IPv4</th>
                        <th onclick="sortTable(2, 'ipam-table', false)">Subnet</th>
                        <th onclick="sortTable(3, 'ipam-table', false)">Network</th>
                    </tr>
                </thead>
                <tbody>

                    <?php
                foreach ($details as $d) {

                    // gataway IP's
                    foreach( $d['IPAM']['Config'] as $ipam ) {
                        print "                    <tr>";
                        print "<td>Gateway</td>";
                        print "<td>{$ipam['Gateway']}".substr($ipam['Subnet'], strpos($ipam['Subnet'], "/") )."</td>";
                        print "<td>{$ipam['Subnet']}</td>";
                        print "<td>{$d['Name']}</td>";
                        print "</tr>\n";
                    }

                    // Container IP's
                    foreach( $d['Containers'] as $k=>$c ) {
                        print "                    <tr>";
                        print "<td>{$c['Name']}</td>";
                        print "<td>{$c['IPv4Address']}</td>";
                        print "<td>".($d['IPAM']['Config'][0]['Subnet']?:' - ')."</td>";
                        print "<td>{$d['Name']}</td>";
                        print "</tr>\n";
                    }

                }
                ?>
                </tbody>
            </table>
        </div>

    <!-- ----------------------------------------------------------------------------------------------------------- -->
    <!-- Ports                                                                                                       -->
    <!-- 1 line per IP per container - a container with 2 IP's has here 2 lines...                                   -->
    <!-- ----------------------------------------------------------------------------------------------------------- -->
        <h1 class="section-title" id="ports">
            Ports <nbsp> <sup id="ports-span">0</sup>
            <a href="#top" class="back-to-top" aria-label="Back to top">
                <!-- Simple up arrow SVG icon -->
                <svg viewBox="0 0 24 24">
                <path d="M12 4l-8 8h5v8h6v-8h5z"/>
                </svg>
            </a>
        </h1>
        <div class="table-container">
            <table id="ports-table">
                <thead>
                    <tr>
                        <th onclick="sortTable(0, 'ports-table', true)">PublicPort</th>
                        <!-- th onclick="sortTable(1, 'ports-table', false)">IP</th -->
                        <th onclick="sortTable(1, 'ports-table', false)">Type</th>
                        <th onclick="sortTable(2, 'ports-table', true)">PrivatePort</th>
                        <th onclick="sortTable(3, 'ports-table', false)">Container</th>
                        <th onclick="sortTable(4, 'ports-table', false)">Image</th>
                    </tr>
                </thead>
                <tbody>

<?php
                foreach ($containers as $c) {

                    foreach( $c['Ports'] as $k=>$p ) {
			            if( $p['IP']=="::" ) continue;

                        print "                    <tr>";
                        print "<td style='text-align: right;'>{$p['PublicPort']}</td>";
                        // print "<td>{$p['IP']}</td>";
                        print "<td>{$p['Type']}</td>";
                        print "<td style='text-align: right;'>{$p['PrivatePort']}</td>";
                        print "<td>".substr( $c['Names'][0], 1 )."</td>";
                        // print "<td>". ( str_contains($c['Image'], ":") ? substr( $c['Image'], 0, strpos($c['Image'], ":") ) : $c['Image'] )."</td>";
                        // print "<td>". ( str_contains($c['Image'], ":") ? substr( $c['Image'], 0, strpos($c['Image'], ":") ) : $c['Image'] )."</td>";
                        print "<td>". ( str_contains($c['Image'], ":") ? ( str_contains($c['Image'], "sha256") ? substr( $c['ImageID'], strpos($c['ImageID'], ":")+1, 12 ) : substr( $c['Image'], 0, strpos($c['Image'], ":") ) ) : $c['Image'] )."</td>";

                        print "</tr>\n";
                    }

                }
?>
                </tbody>
            </table>
        </div>




    <!-- ----------------------------------------------------------------------------------------------------------- -->
    <!-- Images                                                                                                      -->
    <!-- 1 line per IP per container - a container with 2 IP's has here 2 lines...                                   -->
    <!-- ----------------------------------------------------------------------------------------------------------- -->
    <h1 class="section-title" id="images">
            Images <nbsp> <sup id="images-span">0</sup>
            <a href="#top" class="back-to-top" aria-label="Back to top">
                <!-- Simple up arrow SVG icon -->
                <svg viewBox="0 0 24 24">
                <path d="M12 4l-8 8h5v8h6v-8h5z"/>
                </svg>
            </a>
        </h1>
        <div class="table-container">
            <table id="images-table">
                <thead>
                    <tr>
                        <th onclick="sortTable(0, 'images-table', false)">Image</th>
                        <th onclick="sortTable(1, 'images-table', false)">Tag</th>
                        <th onclick="sortTable(2, 'images-table', false)">ImageID</th>
                        <th onclick="sortTable(3, 'images-table', true)">#Running</th>
                        <th onclick="sortTable(4, 'images-table', true)">#Used</th>
                        <th onclick="sortTable(5, 'images-table', true)">MB</th>
                        <th onclick="sortTable(6, 'images-table', false)">Created</th>
                        <th onclick="sortTable(7, 'images-table', false)">Labels</th>
                    </tr>
                </thead>
                <tbody>

                    <?php
                foreach ($images as $img) {
                    $img_used=0;
                    $img_used_running=0;
                    list( $repo, $tag ) = explode( ":", $img['RepoTags'][0], 2 );
			if( $repo=="" ){
				list( $repo, $dummy ) = explode( "@", $img['RepoDigests'][0], 2 );
				// $tag=substr( $img['Id'], strpos($img['Id'], ":")+1, 12 );
                $tag="<span style='color: red;''>?NotUsed?</span>";
			}

		    foreach( $containers as $k=>$c ) {
			    if( $img['Id']==$c['ImageID'] ) {
			        $img_used++;
                    if( $c['State']=='running' ) {
                        $img_used_running++;
                    }
                }
		    }

                    print "                    <tr>";
                    print "<td>{$repo}</td>";
                    print "<td>{$tag}</td>";
                    print "<td>".substr( $img['Id'], strpos($img['Id'], ":")+1, 12 )."</td>";
                //    print "<td>". ( $img_used_running>0 ? $img_used_running : "<span style='color: red;font-weight: bold'>".$img_used_running."</span>" ) ."</td>";
                    print "<td style='text-align: right;'>". ( $img_used_running>0 ? ( $img_used_running==1 ? $img_used_running : "<span style='font-weight: bold'>".$img_used_running."</span>" ) : "<span style='color: red;'>".$img_used_running."</span>" ) ."</td>";
                    print "<td style='text-align: right;'>". ( $img_used>0 ? ( $img_used==1 ? $img_used : "<span style='font-weight: bold'>".$img_used."</span>" ) : "<span style='color: red;'>".$img_used."</span>" ) ."</td>";
                //    print "<td>". ( $img_used>0 ? $img_used : "<span style='color: red;font-weight: bold'>".$img_used."</span>" ) ."</td>";
                    print "<td style='text-align: right;'>".str_pad( round($img['Size']/1024/1024 ,0), 8, ' ', STR_PAD_LEFT)."</td>";
                    print "<td><span title=\"".gmdate("Y-m-d\TH:i:s\Z", $img['Created'])."\">".gmdate("Y-m-d", $img['Created'])."</span></td>";
			$labels_hide = array( 
				 'com.docker.compose.image'
				,'com.docker.compose.oneoff'
				,'com.docker.compose.config-hash'
				,'com.docker.compose.image'
				,'com.docker.compose.depends_on'
				,'com.docker.compose.project.config_files'
				,'com.docker.compose.project.working_dir'
				,'com.docker.compose.container-number'
				,'org.opencontainers.image.revision' 
				,'org.opencontainers.image.url'
				,'org.opencontainers.image.created'
				,'org.opencontainers.image.vendor'
				,'com.vmware.cp.artifact.flavor'
			);
			$img_labels = array();
			foreach( $img['Labels'] as $k=>$v ) {
				if( in_array( $k, $labels_hide ) ) continue;
				$img_labels[]="<span title=\"{$k}\">{$v}</span>";
			}
                    print "<td>".implode("<br />",$img_labels)."</td>";
                    print "</tr>\n";

                }
                ?>
                </tbody>
            </table>
        </div>


 
    <!-- ----------------------------------------------------------------------------------------------------------- -->
    <!-- Containers                                                                                                  -->
    <!-- 1 line per IP per container - a container with 2 IP's has here 2 lines...                                   -->
    <!-- ----------------------------------------------------------------------------------------------------------- -->
        <h1 class="section-title" id="containers">
            Containers <nbsp> <sup id="containers-span">0</sup>
            <a href="#top" class="back-to-top" aria-label="Back to top">
                <!-- Simple up arrow SVG icon -->
                <svg viewBox="0 0 24 24">
                <path d="M12 4l-8 8h5v8h6v-8h5z"/>
                </svg>
            </a>
        </h1>
        <div class="table-container">
            <table id="containers-table">
                <thead>
                    <tr>
                        <th onclick="sortTable(0, 'containers-table', false)">Container</th>
                        <th onclick="sortTable(1, 'containers-table', false)">ContainerId</th>
                        <th onclick="sortTable(2, 'containers-table', false)">Image</th>
                        <th onclick="sortTable(3, 'containers-table', false)">ImageID</th>
                        <th onclick="sortTable(4, 'containers-table', false)">Network</th>
                        <th onclick="sortTable(5, 'containers-table', false)">IP</th>
                        <th onclick="sortTable(6, 'containers-table', false)">State</th>
                        <th onclick="sortTable(7, 'containers-table', false)">Status</th>
                        <th onclick="sortTable(8, 'containers-table', true)">Ports</th>
                    </tr>
                </thead>
                <tbody>

                    <?php
                foreach ($containers as $c) {



		    $ports=array();
                    foreach( $c['Ports'] as $k=>$p ) {
			if( $p['IP']=="::" ) continue;

			if( $p['PublicPort']<>'' ) $ports[$p['PublicPort']]++;
                    }
		    ksort($ports);



		    foreach( $c['NetworkSettings']['Networks'] as $n=>$nn ) {
                        print "                    <tr>";
                        print "<td>".substr( $c['Names'][0], 1 )."</td>";
                        print "<td>".substr( $c['Id'], strpos($c['Id'], ":")+1, 12 )."</td>";
                        print "<td>". ( str_contains($c['Image'], ":") ? ( str_contains($c['Image'], "sha256") ? substr( $c['ImageID'], strpos($c['ImageID'], ":")+1, 12 ) : substr( $c['Image'], 0, strpos($c['Image'], ":") ) ) : $c['Image'] )."</td>";
                        print "<td>".substr( $c['ImageID'], strpos($c['ImageID'], ":")+1, 12 )."</td>";

                        print "<td>{$n}</td>";
			
                        print "<td>". ( $nn['IPPrefixLen']==0 ? "-" : "{$nn['IPAddress']}/{$nn['IPPrefixLen']}" ) ."</td>";
                        print "<td>". ( $c['State']=="running" ? $c['State'] : "<span style='font-style: italic'>".$c['State']."</span>" ) ."</td>";

                        // print "<td>". str_replace( array('Up'), array("<span style='color: green;font-weight: bold'>".$img_used."</span>"), $c['Status'] ) ."</td>";
                        // print "<td>". str_replace( array('Up','unhealthy','healthy'), array("<span style='color: green;'>Up</span>","<span style='color: red;'>unhealthy</span>","<span style='color: green;'>healthy</span>"), $c['Status'], 1 ) ."</td>";
                        // print "<td>". str_replace( array('unhealthy','healthy'), array("<span style='color: red;'>unhealthy</span>","<span style='color: green;'>healthy</span>"), $c['Status'] ) ."</td>";

                        print "<td>". strtr( $c['Status'], array('unhealthy'=>"<span style='color: red;'>unhealthy</span>",'healthy'=>"<span style='color: green;'>healthy</span>") ) ."</td>";



                        print "<td style='text-align: right;'>".implode( " - ", array_keys($ports) )."</td>";

                        print "</tr>\n";
		    }
                }
                ?>
                </tbody>
            </table>
        </div>

    <!-- ----------------------------------------------------------------------------------------------------------- -->



        
    </div>

    <script>
        let isAscending = true;

        // Sorting function for tables
        function sortTable(colIndex, tableId, isNumeric) {
            const table = document.getElementById(tableId);
            const rows = Array.from(table.rows).slice(1); // Skip header row
            const sortedRows = rows.sort((a, b) => {
                if (isNumeric) {
                    const aCell = a.cells[colIndex].innerText.trim();
                    const bCell = b.cells[colIndex].innerText.trim();
                    // return parseFloat(aCell) - parseFloat(bCell);
                    return isAscending ? parseFloat(bCell) - parseFloat(aCell) : parseFloat(aCell) - parseFloat(bCell);
                } else {
                    const cellA = a.cells[colIndex].innerText;
                    const cellB = b.cells[colIndex].innerText;
                    return isAscending ? cellA.localeCompare(cellB) : cellB.localeCompare(cellA);
                }
            });

            isAscending = !isAscending; // Toggle sorting direction
            table.tBodies[0].append(...sortedRows); // Reorder rows in table
            updateHeaderArrow(tableId, colIndex);
        }

        // Update sorting direction arrows
        function updateHeaderArrow(tableId, colIndex) {
            const headers = document.querySelectorAll(`#${tableId} th`);
            headers.forEach((header, index) => {
                if (index === colIndex) {
                    header.classList.toggle('sorted-asc', isAscending);
                    header.classList.toggle('sorted-desc', !isAscending);
                } else {
                    header.classList.remove('sorted-asc', 'sorted-desc');
                }
            });
        }

        function clearSearchAndCalculateSums() {
            searchInput.value = '';
            clearBtn.style.display = 'none';

            table_ids.forEach(table_id => {
                const table = document.getElementById( table_id.concat("-table") );
                const rows = Array.from(table.querySelectorAll('tbody tr'));
                row_count = 0;

                rows.forEach(row => {
                    row.style.display = '';
                    row_count++;
                });
                let sum_id = table_id.concat("-span");
                // document.getElementById(sum_id).innerHTML = row_count==0 ? ' ' : ' '+row_count;
                document.getElementById(sum_id).innerHTML = row_count;
                document.getElementById(table_id).style.display = '';
                document.getElementById(table_id.concat("-table")).style.display = '';
                document.getElementById(table_id.concat("-icon")).style.display = '';

            });
        }

        // Search functionality for filtering rows
        const searchInput = document.getElementById('searchBar');
        const clearBtn = document.getElementById('clearSearchBtn');
        // const tables = [document.getElementById('networks-table'), document.getElementById('ipam-table'), document.getElementById('ports-table'), document.getElementById('images-table'), document.getElementById('containers-table')];
        // const table_ids = ['networks-table','ipam-table','ports-table','images-table','tablcontainers-table'];
        const table_ids = ['networks','ipam','ports','images','containers'];


        // Listen for input in the search bar
        searchInput.addEventListener('input', function () {
            const searchValue = searchInput.value.toLowerCase();
            clearBtn.style.display = searchValue ? 'inline-block' : 'none';

            table_ids.forEach(table_id => { 
                const table = document.getElementById( table_id+'-table' );
                const rows = Array.from(table.querySelectorAll('tbody tr'));
                row_count = 0;
                rows.forEach(row => {
                    const rowText = row.innerText.toLowerCase();
                    if (rowText.includes(searchValue)) {
                        row.style.display = '';
                        row_count++;
                    } else {
                        row.style.display = 'none'
                    }
                });
                let sum_id = table_id.concat("-span");
                document.getElementById(sum_id).innerHTML = row_count;
                if (row_count==0) {                                                                 // Hide empty blocks
                    document.getElementById(table_id).style.display = 'none';                       //  h1 title
                    document.getElementById(table_id.concat("-table")).style.display = 'none';      // table
                    document.getElementById(table_id.concat("-icon")).style.display = 'none';      // table
                } else {
                    document.getElementById(table_id).style.display = '';                           //  h1 title
                    document.getElementById(table_id.concat("-table")).style.display = '';          // table
                    document.getElementById(table_id.concat("-icon")).style.display = '';          // table

                }

            })

        });

        // Clear the search input and reset the table display
        clearBtn.addEventListener('click', function () {
            clearSearchAndCalculateSums() 
        });



        


        // Toggle the navigation menu for mobile
        const hamburger = document.getElementById('hamburger');
        const menu = document.getElementById('menu');

        hamburger.addEventListener('click', function () {
            menu.classList.toggle('active');
        });

        // Add click event to cells: fill the search bar with cell data and search
        const allCells = document.querySelectorAll('td');
        allCells.forEach(cell => {
            cell.addEventListener('click', function () {
                searchInput.value = cell.innerText;
                searchInput.dispatchEvent(new Event('input')); // Trigger search
            });
        });

        window.onload = function() {
            clearSearchAndCalculateSums() 
        };
    </script>

</body>
</html>














<!-- network details
    <div>
	<pre>
	<?php print_r( $details ); ?>
	</pre>
    </div>
-->
<!-- Images
    <div>
	<pre>
	<?php print_r( $images ); ?>
	</pre>
    </div>
-->
<!-- Containers
    <div>
	<pre>
	<?php print_r( $containers ); ?>
	</pre>
    </div>
-->

