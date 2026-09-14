<?php
$databaseUrl=getenv('DATABASE_URL');
if(!$databaseUrl){
  $host=getenv('DB_HOST')?:'localhost'; $port=getenv('DB_PORT')?:'5432';
  $name=getenv('DB_NAME')?:'egg_sales_system'; $user=getenv('DB_USER')?:'postgres'; $pass=getenv('DB_PASSWORD')?:'';
  $databaseUrl="pgsql://$user:$pass@$host:$port/$name";
}
$p=parse_url($databaseUrl);
$dsn="pgsql:host={$p['host']};port=".($p['port']??5432).";dbname=".ltrim($p['path']??'','/');
try{$pdo=new PDO($dsn,$p['user']??null,$p['pass']??null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);}
catch(PDOException $e){die("Database connection failed.");}
class CompatResult{
  public function __construct(private $stmt){}
  public function fetch_assoc(){ $r=$this->stmt->fetch(PDO::FETCH_ASSOC); return $r===false?null:$r; }
  public function fetch(){return $this->fetch_assoc();}
  public function rowCount(){return $this->stmt->rowCount();}
}
class CompatStmt{
  public function __construct(private $stmt){}
  private array $vals=[];
  public function bind_param($types,&...$vars){$this->vals=[];foreach($vars as &$v)$this->vals[]=&$v;return true;}
  public function execute($params=null){return $params!==null?$this->stmt->execute(array_values($params)):$this->stmt->execute($this->vals);}
  public function get_result(){return new CompatResult($this->stmt);}
}
class CompatDB{
  public function __construct(private $pdo){}
  private function sql($s){$s=preg_replace('/\bIFNULL\s*\(/i','COALESCE(',$s);$s=preg_replace('/\bCURDATE\s*\(\s*\)/i','CURRENT_DATE',$s);return $s;}
  public function query($sql){return new CompatResult($this->pdo->query($this->sql($sql)));}
  public function prepare($sql){return new CompatStmt($this->pdo->prepare($this->sql($sql)));}
  public function begin_transaction(){$this->pdo->beginTransaction();}
  public function commit(){$this->pdo->commit();}
  public function rollback(){$this->pdo->rollBack();}
  public function real_escape_string($s){return substr($this->pdo->quote($s),1,-1);}
  public function __get($n){return $n==='insert_id'?$this->pdo->lastInsertId():null;}
}
$conn=new CompatDB($pdo);
?>