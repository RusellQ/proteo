<template>
    <div class="container animate__animated animate__backInUp">
      <h1 class="welcome-title">Bienvenid@, {{ userName }}</h1>
      <p class="welcome-text">Puede empezar a realizar sus actividades con el panel de control que se encuentra a su izquierda.</p>
    </div>
  </template>
  
  <script>
  import 'animate.css';
  
  export default {
    props: ['userName']
  }
  </script>
  
  <style scoped>
  .container {
    height: 80vh;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    background-color: #f7f5f2;
    font-family: 'Montserrat', sans-serif;
  }

  .welcome-title {
    font-size: 2rem;
    font-weight: 700;
    color: #3D3935;
    margin-bottom: 0.75rem;
    letter-spacing: -0.02em;
  }

  .welcome-text {
    font-size: 1.05rem;
    color: #78736a;
    max-width: 36rem;
    text-align: center;
    line-height: 1.5;
  }
  </style>
  