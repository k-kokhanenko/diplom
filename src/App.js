import {Halls} from './componens/Halls/Halls';
import {HallConfiguration} from './componens/HallConfiguration/HallConfiguration';
import {PriceConfiguration} from './componens/PriceConfiguration/PriceConfiguration';
import {SessionGrid} from './componens/SessionGrid/SessionGrid';
import {OpenSales} from './componens/OpenSales/OpenSales';

import './App.css';

import { useEffect } from 'react';
import { useDispatch, useSelector } from 'react-redux';

function App() {
  const dispatch = useDispatch();
  const halls = useSelector(state => state.param);

  const getData = async () => {
    const response = await fetch("http://phpsitechecker.ru/halls/", {
      method : "GET",
    });
    const data = await response.json();
    console.log(data);
    dispatch({type: "UPDATE", payload : data.halls});
  }

  useEffect(() => {
    getData();
  }, []);

  return (    
    <>
      <header className="page-header">
        <h1 className="page-header__title">Идём<span>в</span>кино</h1>
        <span className="page-header__subtitle">Администраторррская</span>
      </header>

      <main className="conf-steps">
        <Halls/>
        <HallConfiguration maxRow={10} maxColumn={10}/>
        <PriceConfiguration/>
        <SessionGrid/>
        <OpenSales/>
      </main>
    </>
  );
}

export default App;
