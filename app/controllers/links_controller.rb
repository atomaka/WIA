class LinksController < ApplicationController
  def index
    @links = Link.released

    respond_to do |format|
      format.html
      format.json { render :json => @links }
    end
  end

  def new
  end

  def create
  end

  def show
    @link = Link.get_and_count(params[:id])

    respond_to do |format|
      format.html { redirect_to @link.url }
      format.json { render :json => @link }
      format.xml { render :xml => @link }
    end
  end
end
