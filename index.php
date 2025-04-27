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
        <h1 id="networks">Networks <nbsp> <sup id="table1-span">0</sup></h1>
        <div class="table-container">
            <table id="table1">
                <thead>
                    <tr>
                        <th onclick="sortTable(0, 'table1')">Name</th>
                        <th onclick="sortTable(1, 'table1')">Driver</th>
                        <th onclick="sortTable(2, 'table1')">Project</th>
                        <th onclick="sortTable(3, 'table1')">Subnet</th>
                        <th onclick="sortTable(4, 'table1')">Total</th>
                        <th onclick="sortTable(5, 'table1')">Used</th>
                        <th onclick="sortTable(6, 'table1')">Available</th>
                    </tr>
                </thead>
                <tbody>
<?php

                        foreach ($details as $d) {
                            $network_name = $d['Name'];
                        // if( in_array( $network_name, array('bridge','null','host','overlay','ipvlan','macvlan') ) ) continue

                        if( isset( $d['IPAM']['Config'][0]['Subnet'] ) ) {
                            $subnet = $d['IPAM']['Config'][0]['Subnet'];
                        } else {
                            $subnet = '??';
                        }

                        $used_ips = count( $d['Containers'] );
                        if( isset( $d['IPAM']['Config'][0]['Gateway'] ) ) {
                            $used_ips++;
                        }

                        if( isset( $d['IPAM']['Config'][0]['Subnet'] ) ) {
                            $subnet = $d['IPAM']['Config'][0]['Subnet'];
                        } else {
                            $subnet = '??';
                        }

                            // Bereken IP-adressen
                            $total_ips = calculate_total_ips($subnet);
                            $available_ips = $total_ips - $used_ips - 2;

                            echo "                    <tr>";
                            echo "<td>{$network_name}</td>";
                            echo "<td>{$d['Driver']}</td>";
                            echo "<td>".($d['Labels']['com.docker.compose.project']?:"<i>Default</i>")."</td>";
                            echo "<td>$subnet</td>";
                            //echo "<td>$total_ips</td>";
                            //echo "<td>".($d['Labels']['com.docker.compose.project']?:"<i>Default</i>")."</td>";
                            echo "<td>".($d['Labels']['com.docker.compose.project']? "$total_ips" :"??")."</td>";

                            echo "<td>$used_ips</td>";
                            //echo "<td>$available_ips</td>";
                            echo "<td>".($d['Labels']['com.docker.compose.project']? "$available_ips" :"??")."</td>";

                            echo "</tr>\n";
                        }

                    ?>
                </tbody>
            </table>
        </div>

        <h1 id="ipam">IPAM <span id="table2-span">(0)</span></h1>
        <div class="table-container">
            <table id="table2">
                <thead>
                    <tr>
                        <th onclick="sortTable(0, 'table2')">Hosts</th>
                        <th onclick="sortTable(1, 'table2')">IPv4</th>
                        <th onclick="sortTable(2, 'table2')">Subnet</th>
                        <th onclick="sortTable(3, 'table2')">Network</th>
                    </tr>
                </thead>
                <tbody>

                    <?php
                foreach ($details as $d) {

                    foreach( $d['IPAM']['Config'] as $ipam ) {
                        print "                    <tr>";
                        print "<td>Gateway</td>";
                        print "<td>{$ipam['Gateway']}".substr($ipam['Subnet'], strpos($ipam['Subnet'], "/") )."</td>";
                        print "<td>{$ipam['Subnet']}</td>";
                        print "<td>{$d['Name']}</td>";
                        print "</tr>\n";
                    }

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

        <h1 id="ports">Ports <span id="table3-span">(0)</span></h1>
        <div class="table-container">
            <table id="table3">
                <thead>
                    <tr>
                        <th onclick="sortTable(0, 'table3')">PublicPort</th>
                        <!-- th onclick="sortTable(1, 'table3')">IP</th -->
                        <th onclick="sortTable(2, 'table3')">Type</th>
                        <th onclick="sortTable(3, 'table3')">PrivatePort</th>
                        <th onclick="sortTable(4, 'table3')">Container</th>
                        <th onclick="sortTable(5, 'table3')">Image</th>
                    </tr>
                </thead>
                <tbody>

                    <?php
                foreach ($containers as $c) {

                    foreach( $c['Ports'] as $k=>$p ) {
			if( $p['IP']=="::" ) continue;

                        print "                    <tr>";
                        print "<td>{$p['PublicPort']}</td>";
                        // print "<td>{$p['IP']}</td>";
                        print "<td>{$p['Type']}</td>";
                        print "<td>{$p['PrivatePort']}</td>";
                        print "<td>".substr( $c['Names'][0], 1 )."</td>";
                        print "<td>". ( str_contains($c['Image'], ":") ? substr( $c['Image'], 0, strpos($c['Image'], ":") ) : $c['Image'] )."</td>";
                        print "</tr>\n";
                    }

                }
                ?>
                </tbody>
            </table>
        </div>

        <h1 id="images">Images <span id="table4-span">(0)</span></h1>
        <div class="table-container">
            <table id="table4">
                <thead>
                    <tr>
                        <th onclick="sortTable(0, 'table4')">Image</th>
                        <th onclick="sortTable(1, 'table4')">Tag</th>
                        <th onclick="sortTable(2, 'table4')">ImageID</th>
                        <th onclick="sortTable(3, 'table4')">#Running</th>
                        <th onclick="sortTable(4, 'table4')">#Used</th>
                        <th onclick="sortTable(5, 'table4')">MB</th>
                        <th onclick="sortTable(6, 'table4')">Created</th>
                        <th onclick="sortTable(7, 'table4')">Labels</th>
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
                    print "<td>". ( $img_used_running>0 ? ( $img_used_running==1 ? $img_used_running : "<span style='font-weight: bold'>".$img_used_running."</span>" ) : "<span style='color: red;'>".$img_used_running."</span>" ) ."</td>";
                    print "<td>". ( $img_used>0 ? ( $img_used==1 ? $img_used : "<span style=';font-weight: bold'>".$img_used."</span>" ) : "<span style='color: red;'>".$img_used."</span>" ) ."</td>";
                //    print "<td>". ( $img_used>0 ? $img_used : "<span style='color: red;font-weight: bold'>".$img_used."</span>" ) ."</td>";
                    print "<td>".str_pad( round($img['Size']/1024/1024 ,0), 8, ' ', STR_PAD_LEFT)."</td>";
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


        <h1 id="section-title">
            Containers
            <span id="table5-span">(0)</span>
            <a href="#top" class="back-to-top" aria-label="Back to top">
                <!-- Simple up arrow SVG icon -->
                <svg viewBox="0 0 24 24">
                <path d="M12 4l-8 8h5v8h6v-8h5z"/>
                </svg>
            </a>
        </h1>

        <div class="table-container">
            <table id="table5">
                <thead>
                    <tr>
                        <th onclick="sortTable(0, 'table5')">Container</th>
                        <th onclick="sortTable(1, 'table5')">ContainerId</th>
                        <th onclick="sortTable(2, 'table5')">Image</th>
                        <th onclick="sortTable(3, 'table5')">ImageID</th>
                        <th onclick="sortTable(4, 'table5')">Network</th>
                        <th onclick="sortTable(5, 'table5')">IP</th>
                        <th onclick="sortTable(6, 'table5')">State</th>
                        <th onclick="sortTable(7, 'table5')">Status</th>
                        <th onclick="sortTable(8, 'table5')">Ports</th>
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

            //          print "<td>". ( str_contains($c['Image'], ":") ? substr( $c['Image'], 0, strpos($c['Image'], ":") ) : $c['Image'] )."</td>";
                        print "<td>". ( str_contains($c['Image'], ":") ? ( str_contains($c['Image'], "sha256") ? substr( $c['ImageID'], strpos($c['ImageID'], ":")+1, 12 ) : substr( $c['Image'], 0, strpos($c['Image'], ":") ) ) : $c['Image'] )."</td>";

                        print "<td>".substr( $c['ImageID'], strpos($c['ImageID'], ":")+1, 12 )."</td>";

                        print "<td>{$n}</td>";
			
                        print "<td>". ( $nn['IPPrefixLen']==0 ? "-" : "{$nn['IPAddress']}/{$nn['IPPrefixLen']}" ) ."</td>";
                        print "<td>{$c['State']}</td>";
                        print "<td>{$c['Status']}</td>";
                        //print "<td>". implode( " - ", sort(array_keys($ports)) ) ."</td>";
                        print "<td>".implode( " - ", array_keys($ports) )."</td>";

                        print "</tr>\n";
		    }
                }
                ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        let isAscending = true;

        // Sorting function for tables
        function sortTable(colIndex, tableId) {
            const table = document.getElementById(tableId);
            const rows = Array.from(table.rows).slice(1); // Skip header row
            const sortedRows = rows.sort((a, b) => {
                const cellA = a.cells[colIndex].innerText;
                const cellB = b.cells[colIndex].innerText;
                return isAscending ? cellA.localeCompare(cellB) : cellB.localeCompare(cellA);
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
                const table = document.getElementById( table_id );
                const rows = Array.from(table.querySelectorAll('tbody tr'));
                row_count = 0;

                rows.forEach(row => {
                    row.style.display = '';
                    row_count++;
                });
                let sum_id = table_id.concat("-span");
                document.getElementById(sum_id).innerHTML = row_count==0 ? '' : ' '+row_count;
            });
        }

        // Search functionality for filtering rows
        const searchInput = document.getElementById('searchBar');
        const clearBtn = document.getElementById('clearSearchBtn');
        const tables = [document.getElementById('table1'), document.getElementById('table2'), document.getElementById('table3'), document.getElementById('table4'), document.getElementById('table5')];
        const table_ids = ['table1','table2','table3','table4','table5'];


        // Listen for input in the search bar
        searchInput.addEventListener('input', function () {
            const searchValue = searchInput.value.toLowerCase();
            clearBtn.style.display = searchValue ? 'inline-block' : 'none';

        //    tables.forEach(table => {
        //        const rows = Array.from(table.querySelectorAll('tbody tr'));
        //        rows.forEach(row => {
        //            const rowText = row.innerText.toLowerCase();
        //            row.style.display = rowText.includes(searchValue) ? '' : 'none';
        //        });
        //    });

            table_ids.forEach(table_id => { 
                const table = document.getElementById( table_id );
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
                document.getElementById(sum_id).innerHTML = row_count==0 ? '' : ' '+row_count;
            })

        });

        // Clear the search input and reset the table display
        clearBtn.addEventListener('click', function () {
            clearSearchAndCalculateSums() 
        });



        
        clearBtn.addEventListener('click2', function () {
                searchInput.value = '';
            clearBtn.style.display = 'none';

        //    tables.forEach(table => {
        //        const rows = table.querySelectorAll('tbody tr');
        //        rows.forEach(row => {
        //            row.style.display = '';
        //        });
        //    });

            table_ids.forEach(table_id => {
                const table = document.getElementById( table_id );
                const rows = Array.from(table.querySelectorAll('tbody tr'));
                row_count = 0;

                rows.forEach(row => {
                    row.style.display = '';
                    row_count++;
                });
                let sum_id = table_id.concat("-span");
                document.getElementById(sum_id).innerHTML = row_count==0 ? '' : ' '+row_count;
            });
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














<!-- details
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

