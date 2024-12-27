<?php	

$response = json_encode([
	'result' => false, 
	'message' => 'Неизвестная ошибка'
]);	

const LOGIN = 'ca89474_netology';
const PASSWORD = 'SJa5YKRa';

if (!empty($_REQUEST['url']))
{
    function getMinuteEndPoint($beginTime, $duration)
    {
        $time = explode(':', $beginTime);
        return $time[0] * 60 + $time[1] + $duration;
    }

	function getElementsList(string $table) : array 
	{
		$db = new PDO('mysql:dbname=ca89474_netology;host=localhost', LOGIN, PASSWORD);
		$sth = $db->prepare("SELECT * FROM ".$table." ORDER BY id");
		$sth->execute();
		return $sth->fetchAll(PDO::FETCH_ASSOC);			
	}	

	function deleteHallById(int $id, string &$message) : bool 
	{		
		$db = new PDO('mysql:dbname=ca89474_netology;host=localhost', LOGIN, PASSWORD);
		$stmt = $db->prepare('DELETE FROM cinema_hall WHERE id='.$id);
		if ($stmt->execute()) {			
			// Удаляем связанные данные из таблицы prices
			$stmt = $db->prepare('DELETE FROM prices WHERE hall_id='.$id);
			$stmt->execute();
			
			// Удаляем связанные данные из таблицы seats
			$stmt = $db->prepare('DELETE FROM seats WHERE hall_id='.$id);
			$stmt->execute();

            // Удаляем связанные данные из таблицы sessions
            $stmt = $db->prepare('DELETE FROM sessions WHERE hall_id='.$id);
            $stmt->execute();

			return true;
		}

		$message = 'Возникла ошибка при удалении записи c id='.$id.':'.$stmt->errorInfo();
		return false;
	}

    function deleteSessionById(int $id, string &$message) : bool
    {
        $db = new PDO('mysql:dbname=ca89474_netology;host=localhost', LOGIN, PASSWORD);
        $stmt = $db->prepare('DELETE FROM sessions WHERE id='.$id);
        if ($stmt->execute()) {
            // todo удаляем какие-то связанные данные
            return true;
        }

        $message = 'Возникла ошибка при удалении записи c id='.$id.':'.$stmt->errorInfo();
        return false;
    }

	function getElementByHallId(string $table, int $id, string &$message) : array 
	{
		$db = new PDO('mysql:dbname=ca89474_netology;host=localhost', LOGIN, PASSWORD);
		$stmt = $db->prepare('SELECT * FROM cinema_hall WHERE id='.$id);
		$stmt->execute();
		
		if ($stmt->rowCount() > 0) {	
			$stmt = $db->prepare('SELECT * FROM '.$table.' WHERE hall_id='.$id);
			$stmt->execute();
		
			if ($stmt->rowCount() > 0) {
				$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
			
				// Преобразуем массив в удробный вид для отображения seats[row][column] = type
				$seats = [];
				if ($table == 'seats') {
					for ($i = 0; $i < count($result); $i++) {
						$seat = $result[$i];
						if (!isset($seats[$seat['row_num']])) {
							$seats[$seat['row_num']] = [];
						}
						
						$seats[$seat['row_num']][$seat['column_num']] = $seat['seat_type'];
					}
					
					return $seats;
				}
				
				return $result;				
			}
		} else {			
			$message = 'Кинозала с таким id='. $id .' не существует.';
			return [];
		}	

		return [];
	}

	function getSessionFilmsInfo(string &$message) : array
	{
		$db = new PDO('mysql:dbname=ca89474_netology;host=localhost', LOGIN, PASSWORD);
		$stmt = $db->prepare('SELECT * FROM films WHERE id IN (SELECT DISTINCT films_id FROM sessions)');
		$stmt->execute();				
		
		if ($stmt->rowCount() > 0) {
			$films = $stmt->fetchAll(PDO::FETCH_ASSOC);
									
			foreach ($films as &$film) {							
				$stmt = $db->prepare('SELECT id, hall_id, begin_time FROM sessions WHERE films_id='.$film['id']);
				$stmt->execute();				
				if ($stmt->rowCount() > 0) {
					$sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
					
					$halls = [];
					foreach ($sessions as $session) {
						if (!isset($halls[$session['hall_id']])) {
							
							$stmt = $db->prepare('SELECT name FROM cinema_hall WHERE id='.$session['hall_id']);
							$stmt->execute();				
							$hallName = $stmt->fetchAll(PDO::FETCH_ASSOC);

							$halls[$session['hall_id']] = [
								'name' => $hallName[0]['name'],
								'beginTimes' => []
							];							
						}
						
						$halls[$session['hall_id']]['sessionsId'][] = substr($session['id'], 0, 5);
						$halls[$session['hall_id']]['beginTimes'][] = substr($session['begin_time'], 0, 5);
					}
					
					$film['halls'] = $halls;
				}
			}

			return $films;
		}			
	}	

	$path = parse_url($_REQUEST['url'], PHP_URL_PATH);
	file_put_contents('log.txt', 'path = '.print_r($path, true).PHP_EOL, FILE_APPEND);							
	
	$data = [];
	if (stripos($path, 'films') !== false) {
		$data = $_POST;
		$file = $_FILES['file'];
		//file_put_contents('log.txt', print_r($file, true).PHP_EOL, FILE_APPEND);								
		//file_put_contents('log.txt', print_r($_POST, true).PHP_EOL, FILE_APPEND);				
	} else {
		file_put_contents('log.txt', 'после разбора formData'.PHP_EOL, FILE_APPEND);			
		$data = json_decode(file_get_contents('php://input'), true);
	}
	

	switch ($_SERVER['REQUEST_METHOD']) 
	{		
		case "POST":
			if ($path == 'halls/') {	
				if (isset($data['name']) && !empty($data['name'])) {
					$db = new PDO('mysql:dbname=ca89474_netology;host=localhost', LOGIN, PASSWORD);
					$stmt = $db->prepare('INSERT INTO cinema_hall (name) VALUES (:name)');
					$stmt->bindParam(':name', $data['name']);
					if ($stmt->execute()) {
						$hallId = $db->lastInsertId();
						
						// Добавляем для кинотеатра данные в таблицу prices
						$stmt = $db->prepare('INSERT INTO prices (hall_id, standart_price, vip_price) VALUES ('.$hallId.', 0, 0)');
						$stmt->execute();

						// Добавляем для кинотеатра данные в таблицу seats
						for ($row = 1; $row <= 5; $row++) {
							for ($column = 1; $column <= 5; $column++) {
								// seat_type : 1-standart, 2-vip, 3-blocked
								$stmt = $db->prepare('INSERT INTO seats (hall_id, row_num, column_num, seat_type) VALUES ('.$hallId.', '.$row.', '.$column.', 1)');
								$stmt->execute();
							}
						}											
						
						$response = json_encode([
							'result' => true,
							'halls' => getElementsList('cinema_hall'),
						]);					
					} else {
						$response = json_encode([
							'result' => false,
							'message' => 'Возникла ошибка при добавлении нового кинозала: '.$stmt->errorInfo(),
						]);						
					}
				} else {
					$response = json_encode([
						'result' => false,
						'message' => 'Невозможно добавить новый кинозал, не передано название.',
					]);					
				}		
			} else
			if ($path == 'tickets/') {			
				if (!empty($data['hallId']) && !empty($data['filmId']) && !empty($data['beginTime']) && !empty($data['selectedSeats']) && !empty($data['totalCost'])/* && !empty($data['selectedDay'])*/) {
					// Определяем sessions_id
					$db = new PDO('mysql:dbname=ca89474_netology;host=localhost', LOGIN, PASSWORD);
					$stmt = $db->prepare('SELECT id FROM sessions WHERE films_id='.$data['filmId'].' AND hall_id='.$data['hallId'].' AND begin_time="'.$data['beginTime'].':00"');					
					$stmt->execute();				
					if ($stmt->rowCount() > 0) {
						$session = $stmt->fetchAll(PDO::FETCH_ASSOC)[0];
						
						//file_put_contents('log.txt', 'sessions_id - '.$session['id'].PHP_EOL, FILE_APPEND);
						
						// Создаем новый билет
						$stmt = $db->prepare('INSERT INTO tickets (sessions_id, date, total_cost) VALUES (:sessions_id, :date, :total_cost)');
						$stmt->bindParam(':sessions_id', $session['id']);
						$stmt->bindParam(':total_cost', $data['totalCost']);						

						$ticketDate = date('Y-m-d', strtotime('+'.$data['selectedDay'].' days', time()));						
						$stmt->bindParam(':date', $ticketDate);
									
						if ($stmt->execute()) {
							$ticketId = $db->lastInsertId();
							
							// Определяем seats_id
							foreach($data['selectedSeats'] as $seat) {
								$parts = explode('-', $seat);								
								//file_put_contents('log.txt', print_r($parts, true).PHP_EOL, FILE_APPEND);
								
								$stmt = $db->prepare('SELECT id FROM seats WHERE hall_id='.$data['hallId'].' AND row_num='.$parts[0].' AND column_num='.$parts[1]);					
								$sql = 'SELECT id FROM sets WHERE hall_id='.$data['hallId'].' AND row_num='.$parts[0].' AND column_num='.$parts[1];
								file_put_contents('log.txt', print_r($sql, true).PHP_EOL, FILE_APPEND);
								$stmt->execute();				
								if ($stmt->rowCount() > 0) {
									$seat = $stmt->fetchAll(PDO::FETCH_ASSOC)[0];
							
									// Выбранное место сохраняем в таблице tickets_seats
									$stmt = $db->prepare('INSERT INTO tickets_seats (seats_id, tickets_id) VALUES (:seats_id, :tickets_id)');
									$stmt->bindParam(':seats_id', $seat['id']);
									$stmt->bindParam(':tickets_id', $ticketId);
									$stmt->execute();				
								}
							}
							
							$response = json_encode([
								'result' => true,
								'ticketId' => $ticketId,
							]);																
						}																
					}										
				} else {
					$response = json_encode([
						'result' => false,
						'message' => 'Невозожно сохранить билеты, не переданы данные.',
					]);						
				}
			} else
			if ($path == 'films/') {	
				if (!empty($data['name']) && !empty($data['duration']) && !empty($data['description']) && !empty($data['country'])) {											
					$file = $_FILES['file'];
					$uploadDirectory = 'images/';
										
					$extension = pathinfo(basename($file['name']), PATHINFO_EXTENSION);					
					$filePath = $uploadDirectory . uniqid() . '.' . $extension;
					move_uploaded_file($file['tmp_name'], $filePath);
  							
					$db = new PDO('mysql:dbname=ca89474_netology;host=localhost', LOGIN, PASSWORD);
					$stmt = $db->prepare('INSERT INTO films (name, duration, description, country, images) VALUES (:name, :duration, :description, :country, :images)');
					$stmt->bindParam(':name', $data['name']);
					$stmt->bindParam(':duration', $data['duration']);
					$stmt->bindParam(':description', $data['description']);
					$stmt->bindParam(':country', $data['country']);
					
					$filePath = "http://phpsitechecker.ru/".$filePath;
					$stmt->bindParam(':images', $filePath);
					
					if ($stmt->execute()) {
						$filmId = $db->lastInsertId();						
						$response = json_encode([
							'result' => true,
							'films' => getElementsList('films'),
						]);											
					} else {
						$response = json_encode([
							'result' => false,
							'message' => 'Возникла ошибка при добавлении нового фильма: '.$stmt->errorInfo(),
						]);						
					}						
				} else {
					$response = json_encode([
						'result' => false,
						'message' => 'Невозожно добавить новый фильм, не переданы названия.',
					]);						
				}
			} else
            // СЕТКА СЕАНСОВ
			if ($path == 'sessions/') {	
				if (!empty($data['hallId']) && !empty($data['filmId']) && !empty($data['beginTime'])) {
                    // Проверка есть ли такой фильм
                    $db = new PDO('mysql:dbname=ca89474_netology;host=localhost', LOGIN, PASSWORD);
                    $stmt = $db->prepare('SELECT id FROM films WHERE id='.$data['filmId']);
                    $stmt->execute();

                    if ($stmt->rowCount() > 0) {
                        // Проверка есть ли такой кинозал
                        $stmt = $db->prepare('SELECT id FROM cinema_hall WHERE id='.$data['hallId']);
                        $stmt->execute();

                        if ($stmt->rowCount() > 0) {
                            // Проверяем, чтобы dataTime + duration не привышали 24часа
                            if (getMinuteEndPoint($data['beginTime'], $data['duration']) < 24 * 60) {
                                // Проверяем dataTime и duration чтобы в этом диапазоне не было какого-то фильма

                                $leftOk = false;
                                $rightOk = false;

                                // Определяем, есть ли в сетке фильм, которые начинается раньше добавляемого фильма
                                $stmt = $db->prepare('SELECT begin_time,duration FROM sessions WHERE begin_time < :begin_time LIMIT 1');
                                $stmt->bindParam(':begin_time', $data['beginTime']);
                                $stmt->execute();
                                if ($stmt->rowCount() > 0) {
                                    $item = $stmt->fetch(PDO::FETCH_ASSOC);
                                    if (getMinuteEndPoint($data['beginTime'], 0) > getMinuteEndPoint($item['begin_time'], $item['duration'])) {
                                        $leftOk = true;
                                    }
                                } else {
                                    $leftOk = true;
                                }

                                // Определяем, есть ли в сетке фильм, которые начинается позже добавляемого фильма
                                $stmt = $db->prepare('SELECT begin_time,duration FROM sessions WHERE begin_time > :begin_time LIMIT 1');
                                $stmt->bindParam(':begin_time', $data['beginTime']);
                                $stmt->execute();
                                if ($stmt->rowCount() > 0) {
                                    $item = $stmt->fetch(PDO::FETCH_ASSOC);
                                    if (getMinuteEndPoint($data['beginTime'], $data['duration']) < getMinuteEndPoint($item['begin_time'], 0)) {
                                        $rightOk = true;
                                    }
                                } else {
                                    $rightOk = true;
                                }

                                if ($leftOk && $rightOk) {
                                    $db = new PDO('mysql:dbname=ca89474_netology;host=localhost', LOGIN, PASSWORD);
                                    $stmt = $db->prepare('INSERT INTO sessions (films_id, hall_id, begin_time, duration) VALUES (:films_id, :hall_id, :begin_time, :duration)');
                                    $stmt->bindParam(':films_id', $data['filmId']);
                                    $stmt->bindParam(':hall_id', $data['hallId']);
                                    $stmt->bindParam(':begin_time', $data['beginTime']);
                                    $stmt->bindParam(':duration', $data['duration']);
                                    if ($stmt->execute()) {
                                        $response = json_encode([
                                            'result' => true,
                                            'sessions' => getElementsList('sessions'), // только нужно возвращать для удобства еще не только id фильма а сразу название фильма тоже
                                        ]);
                                    }
                                } else {
                                    $response = json_encode([
                                        'result' => false,
                                        'message' => 'Невозможно добавить фильм в сетку, т.к. в этом промежутке уже есть другой фильм.',
                                    ]);
                                }
                            } else {
                                $response = json_encode([
                                    'result' => false,
                                    'message' => 'Невозможно добавить фильм в сетку, т.к. он не успеет закончиться в текущие сутки.',
                                ]);
                            }
                        } else {
                            $response = json_encode([
                                'result' => false,
                                'message' => 'Невозможно добавить фильм в сетку, кинозал с id='.$data['hallId'].' не существует.',
                            ]);
                        }
                    } else {
                        $response = json_encode([
                            'result' => false,
                            'message' => 'Невозможно добавить фильм в сетку, фильм с id='.$data['filmId'].' не существует.',
                        ]);
                    }
				} else {
					$response = json_encode([
						'result' => false,
						'message' => 'Невозможно добавить фильм в сетку, не переданы нужные параметры.',
					]);											
				}
			} else {
				$parts = preg_split('@/@', $path, -1, PREG_SPLIT_NO_EMPTY);	
				file_put_contents('log.txt', print_r($parts, true).PHP_EOL, FILE_APPEND);
				
				if (count($parts) == 2 && $parts[0] == 'seats') {		
					$data = json_decode(file_get_contents('php://input'), true);
					file_put_contents('log.txt', print_r($data, true).PHP_EOL, FILE_APPEND);
					
					if (isset($data['row']) && isset($data['column']) && isset($data['seats'])) {													
						// Обновляем данные по row и column
						$db = new PDO('mysql:dbname=ca89474_netology;host=localhost', LOGIN, PASSWORD);
						$stmt = $db->prepare('UPDATE `cinema_hall` SET `row`='.$data['row'].', `column`='.$data['column'].' WHERE id='.intval($parts[1]));
						if ($stmt->execute()) {
							
							// Удаляем текущие данные seats
							$stmt = $db->prepare('DELETE FROM seats WHERE hall_id='.intval($parts[1]));
							if ($stmt->execute()) {							
								// Сохраняем новые значения
								foreach ($data['seats'] as $rowId => $rowInfo) {
									foreach ($rowInfo as $columnId => $columnInfo) {
										$stmt = $db->prepare('INSERT INTO seats (hall_id, row_num, column_num, seat_type) VALUES ('.intval($parts[1]).', '.$rowId.', '.$columnId.', '.$columnInfo.')');
										$stmt->execute();
									}
								}
							}											
							
							$response = json_encode([
								'result' => true,
								'message' => 'Данные успешно обновлены.',
							]);	
						} else {
							file_put_contents('log.txt', '3'.PHP_EOL, FILE_APPEND);
							
							/*$response = json_encode([
								'result' => false,
								'message' => 'Ошибка при обновлении информации по row и column кинозала '.$parts[1].': '.$stmt->errorInfo(),
							]);*/	
						}																	
					} /*else {
						$response = json_encode([
							'result' => false,
							'message' => 'Невозожно сохранить конфигурацию зала, не переданы значения.',
						]);	
					}*/						
				}				
			}				
			break;		

		case "GET":		
			$message = '';		
			$parts = preg_split('@/@', $path, -1, PREG_SPLIT_NO_EMPTY);
			
			
			
			if (count($parts) == 1 && $parts[0] == 'halls') {		
				$response = json_encode([
					'result' => true,
					'halls' => getElementsList('cinema_hall'),
				]);
			} else
			if (count($parts) == 1 && $parts[0] == 'films') {		
				$response = json_encode([
					'result' => true,
					'films' => getElementsList('films'),
				]);
			} else	
			if (count($parts) == 1 && $parts[0] == 'sessions') {		
				$response = json_encode([
					'result' => true,
					'sessions' => getElementsList('sessions'),
				]);
			} else
			if (count($parts) == 1 && $parts[0] == 'sessionsFilms') {						
				$filmsInfo = getSessionFilmsInfo($message);
				if (count($filmsInfo)) {
					$response = json_encode([
						'result' => true, 
						'filmsInfo' => $filmsInfo,
					]);									
				} else {
					$response = json_encode([
						'result' => false, 
						'message' => $message
					]);									
				}				
			} else				
			if (count($parts) == 2 && $parts[0] == 'prices') {		
				$price = getElementByHallId('prices', intval($parts[1]), $message);
				if (count($price)) {
					$response = json_encode([
						'result' => true, 
						'price' => $price,
					]);									
				} else {
					$response = json_encode([
						'result' => false, 
						'message' => $message
					]);									
				}				
			} else
			if (count($parts) == 2 && $parts[0] == 'seats') {		
				$seats = getElementByHallId('seats', intval($parts[1]), $message);
				if (count($seats)) {
					$response = json_encode([
						'result' => true, 
						'seats' => $seats,
					]);									
				} else {
					$response = json_encode([
						'result' => false, 
						'message' => $message
					]);									
				}				
			} else 
			if (count($parts) == 4 && $parts[0] == 'seatsTickets') {		
		
				file_put_contents('log.txt', print_r($parts, true).PHP_EOL, FILE_APPEND);							
		
			//if (count($parts) == 3 && $parts[0] == 'seatsTickets') {		
				// Получаем сетку с учетом купленных билетов				
				// $parts[1] - hallId
				// $parts[2] - sessionId
				// $parts[3] - days
				
				$db = new PDO('mysql:dbname=ca89474_netology;host=localhost', LOGIN, PASSWORD);
				$stmt = $db->prepare('SELECT * FROM cinema_hall WHERE id='.intval($parts[1]));
				$stmt->execute();
								
				if ($stmt->rowCount() > 0) {	
					$stmt = $db->prepare('SELECT * FROM seats WHERE hall_id='.intval($parts[1]));
					$stmt->execute();
				
					if ($stmt->rowCount() > 0) {
						$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
				
						$paySeats = [];
				
						// Получаем список билетов, купленных на этот сеанс					
						$days = date('Y-m-d', strtotime('+'.$parts[3].' days', time()));						
						$stmt = $db->prepare('SELECT id FROM tickets WHERE sessions_id='.intval($parts[2]).' and date="'.$days.'"');
						$stmt->execute();
						if ($stmt->rowCount() > 0) {	
							$ticketsIdsList = '';
							$ticketsIds = $stmt->fetchAll(PDO::FETCH_ASSOC);
							foreach($ticketsIds as $ticketsId) {
								$ticketsIdsList .= $ticketsId['id'].',';
							}
							$ticketsIdsList = rtrim($ticketsIdsList, ',');

							//file_put_contents('log.txt', 'ticketsIdsList'.PHP_EOL, FILE_APPEND);
							//file_put_contents('log.txt', print_r($ticketsIdsList, true).PHP_EOL, FILE_APPEND);																							
													
							// Получаем список мест купленных билетов
							$stmt = $db->prepare('SELECT seats_id FROM tickets_seats WHERE tickets_id IN ('.$ticketsIdsList.')');
							$stmt->execute();
							if ($stmt->rowCount() > 0) {	
								$seatsIds = $stmt->fetchAll(PDO::FETCH_ASSOC);
								//file_put_contents('log.txt', 'seatsIds'.PHP_EOL, FILE_APPEND);
								//file_put_contents('log.txt', print_r($seatsIds, true).PHP_EOL, FILE_APPEND);
								
								foreach($seatsIds as $seatsId) {
									$paySeats[] = $seatsId['seats_id'];
								}							
							}														
						}	

						file_put_contents('log.txt', 'paySeats'.PHP_EOL, FILE_APPEND);
						file_put_contents('log.txt', print_r($paySeats, true).PHP_EOL, FILE_APPEND);

					
						// Преобразуем массив в удобный вид для отображения seats[row][column] = type
						$seats = [];
						for ($i = 0; $i < count($result); $i++) {
							$seat = $result[$i];
							if (!isset($seats[$seat['row_num']])) {
								$seats[$seat['row_num']] = [];
							}
							
							// Проверяем нет ли этого места в купленных билетах
							if (isset($paySeats) && in_array($seat['id'], $paySeats)) {
								$seat['seat_type'] = 4;
							}
							
							$seats[$seat['row_num']][$seat['column_num']] = $seat['seat_type']/*4*/;
						}
						
						$response = json_encode([
							'result' => true, 
							'seats' => $seats,
						]);									
					}
				} else {			
					$response = json_encode([
						'result' => false, 
						'message' => 'Кинозала с таким id='. $id .' не существует.'
					]);									
				}							
			}  				
							
			break;
	
		case "DELETE":
			$parts = preg_split('@/@', $path, -1, PREG_SPLIT_NO_EMPTY);			

			// Удаляем выбранный кинозал				
			if (count($parts) == 2 && $parts[0] == 'halls') {
				$message = '';
				$result = deleteHallById($parts[1], $message);
				
				$response = json_encode([
					'result' => $result, 
					'halls' => getElementsList('cinema_hall'),
					'message' => $message
				]);					
			}
            // Удаляем выбранный фильм из сетки сеансов
            if (count($parts) == 2 && $parts[0] == 'sessions') {
                $message = '';
                $result = deleteSessionById($parts[1], $message);

                $response = json_encode([
                    'result' => $result,
                    'halls' => getElementsList('cinema_hall'),
                    'message' => $message
                ]);
            }
            break;
			
		case "UPDATE":
			if ($path == 'prices/') {
				if (!empty($data['id']) && isset($data['standart_price']) && isset($data['vip_price'])) {
					$db = new PDO('mysql:dbname=ca89474_netology;host=localhost', LOGIN, PASSWORD);
					$stmt = $db->prepare('UPDATE prices SET standart_price=:standart_price, vip_price=:vip_price WHERE hall_id='.$data['id']);
					$stmt->bindParam(':standart_price', $data['standart_price']);
					$stmt->bindParam(':vip_price', $data['vip_price']);
					if ($stmt->execute()) {
						$response = json_encode([
							'result' => true,
							'message' => 'Цены успешно обновлены',
						]);					
					} else {
						$response = json_encode([
							'result' => false,
							'message' => 'Возникла ошибка при обновлении цен: '.$stmt->errorInfo(),
						]);						
					}					
				} else {
					$response = json_encode([
						'result' => false,
						'message' => 'Невозможно обновить цены, не переданы необходимые параметры.',
					]);						
				}							
			} else
            if ($path == 'halls/') {
                if (isset($data['active'])) {
                    $db = new PDO('mysql:dbname=ca89474_netology;host=localhost', LOGIN, PASSWORD);
                    $stmt = $db->prepare('UPDATE cinema_hall SET active=:active');
                    $stmt->bindParam(':active', $data['active']);
                    if ($stmt->execute()) {
                        $response = json_encode([
                            'result' => true,
                            'message' => 'Продажи открыты, все кинозалы разблокированы!',
                        ]);
                    } else {
                        $response = json_encode([
                            'result' => false,
                            'message' => 'Возникла ошибка при обновлении данных кинозалов:'.$stmt->errorInfo(),
                        ]);
                    }
                } else {
                    $response = json_encode([
                        'result' => false,
                        'message' => 'Невозможно обновить кинозалы, не переданы необходимые параметры.',
                    ]);
                }
            }
			break;
		
	}	
}

echo $response;								


