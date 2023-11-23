<html>
    <body>
        <form method="POST">
            <table>
                <tr>
                    <td>
                        <label for="">Enter message</label>
                        <input type="text" name="txtMessage">
                        <input type="submit" name="btnSend" value="Send">
                    </td>
                </tr>
                <?php 
                    $host = "127.0.0.1";
                    $port = 3333;
                    if(isset($_POST['btnSend'])){
                        $msg = $_REQUEST['txtMessage'];
                        // $test = "{\"Card_ID\":\"EB A2 EA 56\",\"MAC_ID\":\"08:3A:8D:15:12:BC\",\"OPER_ID\":\"no\"}";
                        // $t = [
                        //     'name' => 'Jeff',
                        //     'age' => 20,
                        //     'active' => true,
                        //     'colors' => 'red',
                        //     'values' => 'foo',
                        // ];
                        // $tj = json_encode($t);
                        
                        $sock = socket_create(AF_INET,SOCK_STREAM,0);
                        socket_connect($sock,$host,$port);
                        socket_write($sock,$msg,strlen($msg));
                        $reply = socket_read($sock, 1024);
                        $reply = trim($reply);
                    }
                ?>
                <tr>
                    <td>
                        <textarea name="" id="" cols="30" rows="10">
                            <?php if(isset($reply)) { echo @$reply; } ?>
                        </textarea>
                        <?php  $of = json_encode($reply);
                                var_dump($of); ?>
                    </td>
                </tr>
            </table>
        </form>
    </body>
</html>