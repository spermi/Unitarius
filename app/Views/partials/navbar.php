 <!--begin::Navbar-Header-->
      <nav class="app-header navbar navbar-expand bg-body">
        <!--begin::Container-->
        <div class="container-fluid">
          <!--begin::Start Navbar Links-->
          <ul class="navbar-nav">
            <li class="nav-item">
              <a class="nav-link" data-lte-toggle="sidebar" href="#" role="button">
                <i class="bi bi-list"></i>
              </a>
            </li>
            <li class="nav-item d-none d-md-block">
              <a href="<?= base_url('/') ?>" class="nav-link">Home</a>
            </li>
            <li class="nav-item d-none d-md-block">
              <a href="#" class="nav-link">Contact</a>
            </li>
          </ul>
          <!--end::Start Navbar Links-->

          <!--begin::End Navbar Links-->
          <ul class="navbar-nav ms-auto">

            <!--begin::Messages Dropdown Menu-->
            <li class="nav-item dropdown">
              <a class="nav-link" data-bs-toggle="dropdown" href="#">
                <i class="bi bi-chat-text"></i>
                <span class="navbar-badge badge text-bg-danger">3</span>
              </a>
              <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end">
                <a href="#" class="dropdown-item">
                  <!--begin::Message-->
                  <div class="d-flex">
                    <div class="flex-shrink-0">
                      <img src="<?= base_url('public/assets/adminlte/img/user1-128x128.jpg') ?>" alt="User Avatar" class="img-size-50 rounded-circle me-3" >
                    </div>
                    <div class="flex-grow-1">
                      <h3 class="dropdown-item-title">
                        Brad Diesel
                        <span class="float-end fs-7 text-danger"
                          ><i class="bi bi-star-fill"></i
                        ></span>
                      </h3>
                      <p class="fs-7">Call me whenever you can...</p>
                      <p class="fs-7 text-secondary">
                        <i class="bi bi-clock-fill me-1"></i> 4 Hours Ago
                      </p>
                    </div>
                  </div>
                  <!--end::Message-->
                </a>
                <div class="dropdown-divider"></div>
                <a href="#" class="dropdown-item">
                  <!--begin::Message-->
                  <div class="d-flex">
                    <div class="flex-shrink-0">
                      <img src="<?= base_url('public/assets/adminlte/img/user8-128x128.jpg') ?>" alt="User Avatar" class="img-size-50 rounded-circle me-3" >
                    </div>
                    <div class="flex-grow-1">
                      <h3 class="dropdown-item-title">
                        John Pierce
                        <span class="float-end fs-7 text-secondary">
                          <i class="bi bi-star-fill"></i>
                        </span>
                      </h3>
                      <p class="fs-7">I got your message bro</p>
                      <p class="fs-7 text-secondary">
                        <i class="bi bi-clock-fill me-1"></i> 4 Hours Ago
                      </p>
                    </div>
                  </div>
                  <!--end::Message-->
                </a>
                <div class="dropdown-divider"></div>
                <a href="#" class="dropdown-item">
                  <!--begin::Message-->
                  <div class="d-flex">
                    <div class="flex-shrink-0">
                      <img src="<?= base_url('public/assets/adminlte/img/user3-128x128.jpg') ?>" alt="User Avatar" class="img-size-50 rounded-circle me-3" >
                    </div>
                    <div class="flex-grow-1">
                      <h3 class="dropdown-item-title">
                        Nora Silvester
                        <span class="float-end fs-7 text-warning">
                          <i class="bi bi-star-fill"></i>
                        </span>
                      </h3>
                      <p class="fs-7">The subject goes here</p>
                      <p class="fs-7 text-secondary">
                        <i class="bi bi-clock-fill me-1"></i> 4 Hours Ago
                      </p>
                    </div>
                  </div>
                  <!--end::Message-->
                </a>
                <div class="dropdown-divider"></div>
                <a href="#" class="dropdown-item dropdown-footer">See All Messages</a>
              </div>
            </li>
            <!--end::Messages Dropdown Menu-->

            <!--begin::Notifications Dropdown Menu-->
            <li class="nav-item dropdown">
              <a class="nav-link" data-bs-toggle="dropdown" href="#">
                <i class="bi bi-bell-fill"></i>
                <span class="navbar-badge badge text-bg-warning">15</span>
              </a>
              <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end">
                <span class="dropdown-item dropdown-header">15 Notifications</span>
                <div class="dropdown-divider"></div>
                <a href="#" class="dropdown-item">
                  <i class="bi bi-envelope me-2"></i> 4 new messages
                  <span class="float-end text-secondary fs-7">3 mins</span>
                </a>
                <div class="dropdown-divider"></div>
                <a href="#" class="dropdown-item">
                  <i class="bi bi-people-fill me-2"></i> 8 friend requests
                  <span class="float-end text-secondary fs-7">12 hours</span>
                </a>
                <div class="dropdown-divider"></div>
                <a href="#" class="dropdown-item">
                  <i class="bi bi-file-earmark-fill me-2"></i> 3 new reports
                  <span class="float-end text-secondary fs-7">2 days</span>
                </a>
                <div class="dropdown-divider"></div>
                <a href="#" class="dropdown-item dropdown-footer"> See All Notifications </a>
              </div>
            </li>
            <!--end::Notifications Dropdown Menu-->

          <!--begin::Color Mode Toggler-->
          <li class="nav-item dropdown">
              <button
                class="btn btn-link nav-link py-2 px-0 px-lg-2 dropdown-toggle d-flex align-items-center"
                id="bd-theme"
                type="button"
                aria-expanded="false"
                data-bs-toggle="dropdown"
                data-bs-display="static"
              >
                <span class="theme-icon-active">
                  <i class="my-1"></i>
                </span>
                <span class="d-lg-none ms-2" id="bd-theme-text">Toggle theme</span>
              </button>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="bd-theme-text" style="--bs-dropdown-min-width: 8rem;" >
              <li>
                <button
                  type="button"
                  class="dropdown-item d-flex align-items-center active"
                  data-bs-theme-value="light"
                  aria-pressed="false"
                >
                  <i class="bi bi-sun-fill me-2"></i> Light
                  <i class="bi bi-check-lg ms-auto d-none"></i>
                </button>
              </li>
              <li>
                <button
                  type="button"
                  class="dropdown-item d-flex align-items-center"
                  data-bs-theme-value="dark"
                  aria-pressed="false"
                >
                  <i class="bi bi-moon-fill me-2"></i> Dark
                  <i class="bi bi-check-lg ms-auto d-none"></i>
                </button>
              </li>
              <li>
                <button
                  type="button"
                  class="dropdown-item d-flex align-items-center"
                  data-bs-theme-value="auto"
                  aria-pressed="true"
                >
                  <i class="bi bi-circle-fill-half-stroke me-2"></i> Auto
                  <i class="bi bi-check-lg ms-auto d-none"></i>
                </button>
              </li>
            </ul> 
              <!--end::Color Mode Toggler-->

              <?php
                // default fallback avatar + name
                $avatar = base_url('public/assets/adminlte/img/user.png');
                $name   = 'Alexander Pierce';

                // use Google OAuth data if present
                if (!empty($_SESSION['oauth_avatar'])) {
                    $avatar = htmlspecialchars($_SESSION['oauth_avatar'], ENT_QUOTES, 'UTF-8');
                }
                if (!empty($_SESSION['oauth_name'])) {
                    $name = htmlspecialchars($_SESSION['oauth_name'], ENT_QUOTES, 'UTF-8');
                }
              ?>
              <!--begin::User Menu Dropdown-->
              <li class="nav-item dropdown user-menu">
                <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">
                  <img src="<?= $avatar ?>" class="user-image rounded-circle shadow" alt="User Image" >
                  <span class="d-none d-md-inline"><?= $name ?></span>
                </a>
                <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-end">
                  <!--begin::User Image-->
                    <li class="user-header text-bg-primary">
                      <img src="<?= $avatar ?>" class="user-image rounded-circle shadow" alt="User Image">
                      <p>
                        <?= $name ?> 
                        <?php if (!empty($_SESSION['auth_provider']) && $_SESSION['auth_provider'] === 'google'): ?>
                          <small><i class="bi bi-google text-warning"></i> Google login</small>
                        <?php else: ?>
                          <small>Local account</small>
                        <?php endif; ?>
                      </p>
                    </li>
                    <!--end::User Image-->

                  <!--begin::Menu Body-->
                  <li class="user-body">
                    <!--begin::Row-->
                    <div class="row">
                    </div>
                    <!--end::Row-->
                  </li>
                  <!--end::Menu Body-->
                  <!--begin::Menu Footer-->
                  <li class="user-footer">
                    <a href="#" class="btn btn-default btn-flat">Profile</a>
                    <a href="<?= base_url('logout') ?>"  class="btn btn-default btn-flat float-end">Sign out</a>
                  </li>
                  <!--end::Menu Footer-->
                </ul>
              </li>
              <!--end::User Menu Dropdown-->
            </ul>
          <!--end::End Navbar Links-->
        </div>
        <!--end::Container-->
      </nav>
      <!--end::Navbar-Header-->